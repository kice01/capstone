<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with(['product', 'supplier', 'batch', 'user', 'batchAllocations.batch'])->latest()->get();

        return view('inventories.index', compact('inventories'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('inventories.create', compact('products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'stock_in' => ['required', 'integer', 'min:0'],
            'stock_out' => ['nullable', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $stockIn = (int) $data['stock_in'];
        $stockOut = (int) ($data['stock_out'] ?? 0);

        if ($stockIn === 0 && $stockOut === 0) {
            throw ValidationException::withMessages(['stock_in' => 'Enter a stock-in or stock-out quantity.']);
        }
        if ($stockIn > 0 && empty($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'Select a supplier for incoming stock.']);
        }
        if ($stockIn > 0 && ($data['unit_cost'] ?? null) === null) {
            throw ValidationException::withMessages(['unit_cost' => 'Enter the purchase cost per unit for incoming stock.']);
        }

        DB::transaction(function () use ($data, $stockIn, $stockOut) {
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $batchTotal = (int) Batch::where('product_id', $product->id)
                ->where('status', 'active')->where('quantity', '>', 0)->sum('quantity');

            if ($stockOut > $batchTotal) {
                throw ValidationException::withMessages([
                    'stock_out' => "Stock Out cannot exceed available stock ({$batchTotal}) for {$product->name}.",
                ]);
            }

            $batchId = null;
            if ($stockIn > 0) {
                $batch = Batch::create([
                    'product_id' => $product->id,
                    'supplier_id' => $data['supplier_id'],
                    'quantity' => $stockIn,
                    'unit_cost' => $data['unit_cost'],
                    'expiration_date' => $data['expiry_date'] ?? null,
                    'date_received' => now()->toDateString(),
                    'status' => 'active',
                    'source' => 'inventory',
                ]);
                $batchId = $batch->id;
            }

            $inventory = Inventory::create([
                'product_id' => $product->id,
                'batch_id' => $batchId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => auth()->id(),
                'reason' => $data['reason'],
                'unit_cost' => $stockIn > 0 ? $data['unit_cost'] : null,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'current_stock' => 0,
                'expiry_date' => $data['expiry_date'] ?? null,
            ]);

            if ($stockOut > 0) {
                $allocations = Batch::deductFefo($product->id, $stockOut);
                $costTotal = 0;
                $knownCostQuantity = 0;
                foreach ($allocations as $allocatedBatchId => $quantity) {
                    $batchCost = Batch::whereKey($allocatedBatchId)->value('unit_cost');
                    if ($batchCost !== null) {
                        $costTotal += (float) $batchCost * $quantity;
                        $knownCostQuantity += $quantity;
                    }
                    $inventory->batchAllocations()->create([
                        'batch_id' => $allocatedBatchId,
                        'quantity' => $quantity,
                    ]);
                }
                if ($stockIn === 0 && $knownCostQuantity === $stockOut) {
                    $inventory->update(['unit_cost' => $costTotal / $stockOut]);
                }
            }

            Batch::syncProductQuantity($product->id);
            $inventory->update(['current_stock' => Product::findOrFail($product->id)->quantity]);

            return $inventory;
        }, 3);

        return redirect()->route('inventories.index')->with('success', 'Inventory movement recorded.');
    }

    public function show(Inventory $inventory)
    {
        return redirect()->route('inventories.index');
    }
}
