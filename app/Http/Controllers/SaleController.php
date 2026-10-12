<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\DailyClosing;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('items')->latest('sale_date')->latest('id')->get();

        return view('sales.index', compact('sales'));
    }

    public function history(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:Cash,GCash'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $sales = Sale::withCount('items')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('id', is_numeric($search) ? (int) $search : -1)
                        ->orWhereHas('items.product', fn ($products) => $products->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('sale_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('sale_date', '<=', $date))
            ->latest('sale_date')->latest('id')
            ->paginate(25)->withQueryString();

        return view('sales.history', compact('sales', 'filters'));
    }

    public function create()
    {
        $availableBatches = function ($query) {
            $query->where('status', 'active')->where('quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiration_date')
                        ->orWhereDate('expiration_date', '>=', now()->toDateString());
                })
                ->orderByRaw('expiration_date IS NULL, expiration_date ASC');
        };

        $products = Product::with(['category', 'batches' => $availableBatches])
            ->withSum(['batches as sellable_quantity' => $availableBatches], 'quantity')
            ->whereHas('batches', $availableBatches)
            ->orderBy('name')->get();

        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'sale_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:Cash,GCash'],
            'cash_tendered' => ['nullable', 'required_if:payment_method,Cash', 'numeric', 'min:0'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($data) {
            if (DailyClosing::whereDate('close_date', now()->toDateString())->exists()) {
                throw ValidationException::withMessages(['sale_date' => 'Today has already been closed. Ask an owner to review the daily closing before recording another sale.']);
            }

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'] ?? null,
                'sale_date' => $data['sale_date'],
                'payment_method' => $data['payment_method'],
                'total_amount' => 0,
            ]);
            $total = 0;

            foreach ($data['product_id'] as $index => $productId) {
                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $quantity = (int) $data['quantity'][$index];
                $available = (int) Batch::where('product_id', $product->id)
                    ->where('status', 'active')->where('quantity', '>', 0)
                    ->where(function ($query) {
                        $query->whereNull('expiration_date')
                            ->orWhereDate('expiration_date', '>=', now()->toDateString());
                    })->sum('quantity');

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'product_id' => "Not enough unexpired stock for {$product->display_name}. Available: {$available}.",
                    ]);
                }

                $item = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);

                $allocations = Batch::deductFefo($product->id, $quantity, true);
                $costTotal = 0;
                $knownCostQuantity = 0;
                foreach ($allocations as $batchId => $allocatedQuantity) {
                    $batchCost = Batch::whereKey($batchId)->value('unit_cost');
                    if ($batchCost !== null) {
                        $costTotal += (float) $batchCost * $allocatedQuantity;
                        $knownCostQuantity += $allocatedQuantity;
                    }
                    $item->batchAllocations()->create([
                        'batch_id' => $batchId,
                        'quantity' => $allocatedQuantity,
                    ]);
                }
                $unitCost = $knownCostQuantity === $quantity ? $costTotal / $quantity : null;
                $item->update(['unit_cost' => $unitCost]);
                Batch::syncProductQuantity($product->id);
                $movement = Inventory::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'stock_in' => 0,
                    'stock_out' => $quantity,
                    'current_stock' => Product::findOrFail($product->id)->quantity,
                    'reason' => 'Sale TXN-'.str_pad((string) $sale->id, 9, '0', STR_PAD_LEFT),
                    'unit_cost' => $unitCost,
                ]);
                foreach ($allocations as $batchId => $allocatedQuantity) {
                    $movement->batchAllocations()->create([
                        'batch_id' => $batchId,
                        'quantity' => $allocatedQuantity,
                    ]);
                }
                $total += $quantity * $product->price;
            }

            $received = $data['payment_method'] === 'GCash'
                ? $total
                : (float) $data['cash_tendered'];
            if ($received < $total) {
                throw ValidationException::withMessages(['cash_tendered' => 'Insufficient cash for this sale.']);
            }

            $sale->update([
                'total_amount' => $total,
                'cash_tendered' => $received,
                'change_amount' => $received - $total,
            ]);

            return $sale;
        }, 3);

        return redirect()->route('sales.show', $sale)->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'items.batchAllocations.batch', 'user']);

        return view('sales.show', compact('sale'));
    }

    public function void(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        DB::transaction(function () use ($sale, $data) {
            $lockedSale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($lockedSale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'This transaction has already been voided.']);
            }

            $lockedSale->load('items.batchAllocations');
            if ($lockedSale->items->isEmpty()) {
                throw ValidationException::withMessages(['sale' => 'This transaction has no recorded items and cannot be voided automatically.']);
            }
            $lockedProductIds = $lockedSale->items->pluck('product_id')->unique()->sort()->values();
            Product::whereIn('id', $lockedProductIds)->orderBy('id')->lockForUpdate()->get();
            $productIds = [];
            $reversalMovements = [];
            foreach ($lockedSale->items as $item) {
                $allocated = (int) $item->batchAllocations->sum('quantity');
                if ($allocated !== 0 && $allocated !== (int) $item->quantity) {
                    throw ValidationException::withMessages([
                        'sale' => 'This transaction has an incomplete batch record and cannot be voided automatically.',
                    ]);
                }

                $movement = Inventory::create([
                    'product_id' => $item->product_id,
                    'user_id' => auth()->id(),
                    'stock_in' => $item->quantity,
                    'stock_out' => 0,
                    'current_stock' => 0,
                    'reason' => 'Void TXN-'.str_pad((string) $lockedSale->id, 9, '0', STR_PAD_LEFT),
                    'unit_cost' => $item->unit_cost,
                ]);

                foreach ($item->batchAllocations as $allocation) {
                    $batch = Batch::whereKey($allocation->batch_id)->lockForUpdate()->firstOrFail();
                    $batch->quantity += $allocation->quantity;
                    $batch->status = 'active';
                    $batch->save();
                    $movement->batchAllocations()->create([
                        'batch_id' => $batch->id,
                        'quantity' => $allocation->quantity,
                    ]);
                }
                if ($allocated === 0) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                    $returnBatch = Batch::create([
                        'product_id' => $product->id,
                        'supplier_id' => null,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'expiration_date' => $product->expiry_date,
                        'date_received' => now()->toDateString(),
                        'status' => 'active',
                        'source' => 'void_return',
                    ]);
                    $movement->batchAllocations()->create([
                        'batch_id' => $returnBatch->id,
                        'quantity' => $item->quantity,
                    ]);
                }
                $productIds[] = $item->product_id;
                $reversalMovements[] = $movement;
            }

            foreach (array_unique($productIds) as $productId) {
                Batch::syncProductQuantity((int) $productId);
            }
            foreach ($reversalMovements as $movement) {
                $movement->update(['current_stock' => Product::findOrFail($movement->product_id)->quantity]);
            }

            $lockedSale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $data['void_reason'],
            ]);
        }, 3);

        return redirect()->route('sales.show', $sale)->with('success', 'Transaction voided and stock restored.');
    }
}
