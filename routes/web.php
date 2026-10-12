<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DailyClosingController;
use App\Http\Controllers\ExpiryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/dashboard', function () {
    // ===== Sales Trend (last 7 days) =====
    $salesRaw = \App\Models\Sale::selectRaw('DATE(sale_date) as date, SUM(total_amount) as total')
        ->where('status', 'completed')
        ->where('sale_date', '>=', now()->subDays(6)->startOfDay())
        ->groupBy('date')
        ->pluck('total', 'date');

    $salesTrendLabels = [];
    $salesTrendData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i)->format('Y-m-d');
        $salesTrendLabels[] = now()->subDays($i)->format('M d');
        $salesTrendData[] = (float) ($salesRaw[$date] ?? 0);
    }

    // ===== Top 5 Best-Selling Products =====
    $topProducts = \App\Models\SaleItem::select('product_id')
        ->whereHas('sale', fn ($query) => $query->where('status', 'completed'))
        ->selectRaw('SUM(quantity) as total_qty')
        ->groupBy('product_id')
        ->orderByDesc('total_qty')
        ->with('product')
        ->take(5)
        ->get();

    // ===== Expiry Status =====
    $today = \Carbon\Carbon::today();
    $nearLimit = \Carbon\Carbon::today()->addDays(15);

    $expiredCount = \App\Models\Product::whereNotNull('expiry_date')
        ->whereDate('expiry_date', '<', $today)->count();

    $nearExpiryCount = \App\Models\Product::whereNotNull('expiry_date')
        ->whereDate('expiry_date', '>=', $today)
        ->whereDate('expiry_date', '<=', $nearLimit)->count();

    $safeCount = \App\Models\Product::whereNotNull('expiry_date')
        ->whereDate('expiry_date', '>', $nearLimit)->count();

    // ===== Low Stock Alert =====
    $lowStockProducts = \App\Models\Product::where('quantity', '<', 20)
        ->orderBy('quantity')
        ->take(5)
        ->get();

    $lowStockCount = \App\Models\Product::where('quantity', '<', 20)->count();

    return view('dashboard', compact(
        'salesTrendLabels', 'salesTrendData',
        'topProducts',
        'expiredCount', 'nearExpiryCount', 'safeCount',
        'lowStockProducts', 'lowStockCount'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Profile (both roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===== OWNER-ONLY: confidential management modules =====
    Route::middleware('role:owner')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::get('/search-suggestions/categories', [CategoryController::class, 'suggestions'])->name('categories.suggestions');
        Route::resource('suppliers', SupplierController::class);

        // Owner-only product management (add/edit/delete)
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::get('/products/check-barcode/{barcode}', [ProductController::class, 'checkBarcode'])->name('products.checkBarcode');
        Route::get('/products/lookup-barcode/{barcode}', [ProductController::class, 'lookupBarcode'])->name('products.lookupBarcode');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Owner-only inventory management (add/edit/delete stock records)
        Route::get('/inventories/create', [InventoryController::class, 'create'])->name('inventories.create');
        Route::post('/inventories', [InventoryController::class, 'store'])->name('inventories.store');

        // Sales Reports
        Route::get('/reports/sales', [\App\Http\Controllers\SalesReportController::class, 'index'])->name('reports.sales');
        Route::get('/reports/daily-close', [DailyClosingController::class, 'index'])->name('reports.daily-close');
        Route::post('/reports/daily-close', [DailyClosingController::class, 'store'])->name('reports.daily-close.store');
        Route::patch('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

        // Expiry Reports
        Route::get('/reports/expiry', [\App\Http\Controllers\ExpiryReportController::class, 'index'])->name('reports.expiry');

        // Inventory Reports
        Route::get('/reports/inventory', [\App\Http\Controllers\InventoryReportController::class, 'index'])->name('reports.inventory');

        // User Management (owner-only)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ===== SHARED: owner + cashier =====
    Route::middleware('role:owner,cashier')->group(function () {
        // Products: view-only for cashier
        Route::get('/search-suggestions/products', [ProductController::class, 'suggestions'])->name('products.suggestions');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

        // Sales / Transactions
        Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);

        // Inventory viewing (stock levels)
        Route::get('/inventories', [InventoryController::class, 'index'])->name('inventories.index');
        Route::get('/inventories/{inventory}', [InventoryController::class, 'show'])->name('inventories.show');

        // Expiry Tracking
        Route::get('/expiry', [ExpiryController::class, 'index'])->name('expiry.index');
    });

});

require __DIR__.'/auth.php';
