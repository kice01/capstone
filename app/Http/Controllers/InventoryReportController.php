<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function index()
    {
        // ===== Stock Levels by Category =====
        $stockByCategory = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category')
            ->selectRaw('SUM(products.quantity) as total_qty')
            ->groupBy('categories.name')
            ->orderByDesc('total_qty')
            ->get();

        // ===== Stock Value Summary (quantity * price) =====
        $stockValueByCategory = DB::table('batches')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category')
            ->selectRaw('SUM(batches.quantity * COALESCE(batches.unit_cost, 0)) as total_value')
            ->where('batches.status', 'active')
            ->groupBy('categories.name')
            ->orderByDesc('total_value')
            ->get();

        $totalStockValue = Batch::where('status', 'active')->sum(DB::raw('quantity * COALESCE(unit_cost, 0)'));
        $stockUnitsWithoutCost = Batch::where('status', 'active')->whereNull('unit_cost')->sum('quantity');
        $totalStockUnits = Product::sum('quantity');

        // ===== Low Stock List =====
        $lowStockProducts = Product::with('category')
            ->where('quantity', '<', 20)
            ->orderBy('quantity')
            ->get();

        // ===== Inventory Movement (last 30 days, stock in vs out) =====
        $movementRaw = Inventory::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(stock_in) as stock_in, SUM(stock_out) as stock_out')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $movementLabels = [];
        $stockInData = [];
        $stockOutData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $movementLabels[] = now()->subDays($i)->format('M d');
            $stockInData[] = isset($movementRaw[$date]) ? (int) $movementRaw[$date]->stock_in : 0;
            $stockOutData[] = isset($movementRaw[$date]) ? (int) $movementRaw[$date]->stock_out : 0;
        }

        return view('reports.inventory', compact(
            'stockByCategory',
            'stockValueByCategory', 'totalStockValue', 'totalStockUnits', 'stockUnitsWithoutCost',
            'lowStockProducts',
            'movementLabels', 'stockInData', 'stockOutData'
        ));
    }
}
