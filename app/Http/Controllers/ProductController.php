<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $suggestedProductId = $request->input('suggested_product_id');

        $products = Product::with('category')
            ->withCount(['saleItems', 'batches'])
            ->addSelect([
                'inventories_count' => DB::table('inventories')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('inventories.product_id', 'products.id'),
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%')
                      ->orWhere('size', 'like', '%' . $search . '%')
                      ->orWhere('unit', 'like', '%' . $search . '%')
                      ->orWhereHas('category', function ($q2) use ($search) {
                          $q2->where('name', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when($suggestedProductId, fn ($query) => $query->whereKey($suggestedProductId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->get();

        $categories = Category::orderBy('name')->get();
        return view('products.index', compact('products', 'search', 'categories', 'categoryId', 'suggestedProductId'));
    }

    public function suggestions(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $products = Product::with('category')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('size', 'like', "%{$term}%")
                    ->orWhere('unit', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->limit(7)
            ->get()
            ->map(fn (Product $product) => [
                'kind' => 'Product',
                'label' => $product->display_name,
                'detail' => trim(($product->category?->name ?? '') . ($product->barcode ? ' · ' . $product->barcode : '')),
                'value' => $product->barcode && str_contains($product->barcode, $term) ? $product->barcode : $product->name,
                'product_id' => $product->id,
            ]);

        $categories = Category::where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'kind' => 'Category',
                'label' => $category->name,
                'detail' => 'Filter products in this category',
                'value' => $category->name,
                'category_id' => $category->id,
            ]);

        return response()->json($products->concat($categories)->take(10)->values());
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:255',
            'size' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:30',
            'barcode' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'opening_unit_cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);
        $initialQuantity = (int) $request->input('quantity', 0);
        DB::transaction(function () use ($request, $initialQuantity) {
            $product = Product::create($request->only([
                'category_id', 'name', 'size', 'unit', 'barcode', 'price',
            ]) + ['quantity' => 0, 'expiry_date' => $request->input('expiry_date')]);

            if ($initialQuantity > 0) {
                $batch = Batch::create([
                    'product_id' => $product->id,
                    'supplier_id' => null,
                    'quantity' => $initialQuantity,
                    'unit_cost' => $request->input('opening_unit_cost'),
                    'expiration_date' => $request->input('expiry_date'),
                    'date_received' => now()->toDateString(),
                    'status' => 'active',
                    'source' => 'opening_balance',
                ]);

                $inventory = \App\Models\Inventory::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'user_id' => auth()->id(),
                    'reason' => 'Opening stock at product creation',
                    'unit_cost' => $request->input('opening_unit_cost'),
                    'stock_in' => $initialQuantity,
                    'stock_out' => 0,
                    'current_stock' => $initialQuantity,
                    'expiry_date' => $request->input('expiry_date'),
                ]);
            }

            Batch::syncProductQuantity($product->id);
        });
        return redirect()->route('products.create')
            ->with('success', 'Product added. You can add another product.');
    }

    public function checkBarcode(string $barcode)
    {
        $products = Product::where('barcode', $barcode)
            ->get(['id', 'name', 'size', 'unit']);

        return response()->json([
            'exists'  => $products->isNotEmpty(),
            'ambiguous' => $products->count() > 1,
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'display_name' => $product->display_name,
            ])->values(),
        ]);
    }

    public function lookupBarcode(string $barcode)
    {
        $cached = DB::table('open_food_facts_cache')->where('barcode', $barcode)->first();
        $isFresh = $cached && Carbon::parse($cached->updated_at)->greaterThan(now()->subDays(90));

        if ($isFresh) {
            return response()->json([
                'found' => (bool) $cached->found,
                'name' => $cached->product_name,
                'categories' => $cached->categories ? json_decode($cached->categories, true) : [],
                'cached' => true,
            ]);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ExTrack/1.0 (barcode product lookup)',
            ])->timeout(5)->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json", [
                'fields' => 'product_name,product_name_en,brands,categories,categories_tags_en',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Open Food Facts request failed.');
            }

            $product = $response->json('product', []);
            $found = (int) $response->json('status', 0) === 1;
            $name = $product['product_name'] ?? $product['product_name_en'] ?? null;
            if ($name && !empty($product['brands'])) {
                $name = trim($product['brands'] . ' ' . $name);
            }

            $categories = $product['categories_tags_en'] ?? [];
            if (!$categories && !empty($product['categories'])) {
                $categories = array_map('trim', explode(',', $product['categories']));
            }
            $categories = array_values(array_unique(array_filter($categories)));

            DB::table('open_food_facts_cache')->updateOrInsert(
                ['barcode' => $barcode],
                [
                    'product_name' => $name,
                    'categories' => json_encode($categories),
                    'found' => $found,
                    'updated_at' => now(),
                    'created_at' => $cached->created_at ?? now(),
                ]
            );

            return response()->json([
                'found' => $found,
                'name' => $name,
                'categories' => $categories,
                'cached' => false,
            ]);
        } catch (\Throwable $e) {
            if ($cached) {
                return response()->json([
                    'found' => (bool) $cached->found,
                    'name' => $cached->product_name,
                    'categories' => $cached->categories ? json_decode($cached->categories, true) : [],
                    'cached' => true,
                    'offline' => true,
                ]);
            }

            return response()->json([
                'found' => false,
                'name' => null,
                'categories' => [],
                'offline' => true,
            ]);
        }
    }

    public function scanForSale(string $barcode)
    {
        $product = Product::where('barcode', $barcode)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $batch = Batch::where('product_id', $product->id)
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereDate('expiration_date', '>=', Carbon::today())
            ->orderBy('expiration_date', 'asc')
            ->first();

        if (!$batch) {
            return response()->json(['message' => 'No available stock for this product'], 404);
        }

        return response()->json([
            'product_id'         => $product->id,
            'name'                => $product->name,
            'price'               => $product->price,
            'batch_id'            => $batch->batch_id,
            'quantity_available'  => $batch->quantity,
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return redirect()->route('products.index');
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:255',
            'size' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:30',
            'barcode' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);
        $product->update($request->only([
            'category_id', 'name', 'size', 'unit', 'barcode', 'price',
        ]));
        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->saleItems()->exists() || $product->batches()->exists()
            || DB::table('inventories')->where('product_id', $product->id)->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'This product has inventory or sales history and cannot be deleted. Edit it or set its quantity to 0 instead.');
        }

        $product->delete();
        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
