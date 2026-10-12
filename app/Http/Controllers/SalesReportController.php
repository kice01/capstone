<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index()
    {
        // ===== Sales by Category =====
        $salesByCategory = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category')
            ->selectRaw('SUM(sale_items.quantity * sale_items.price) as total')
            ->where('sales.status', 'completed')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        // ===== Sales per Month (last 6 months) =====
        $rawSales = Sale::where('status', 'completed')
            ->where('sale_date', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['sale_date', 'total_amount']);

        $grouped = $rawSales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_date)->format('Y-m');
        });

        $monthLabels = [];
        $monthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $monthLabels[] = now()->subMonths($i)->format('M Y');
            $monthData[] = isset($grouped[$key]) ? (float) $grouped[$key]->sum('total_amount') : 0;
        }

        // ===== Payment Method Breakdown =====
        $paymentBreakdown = Sale::where('status', 'completed')->select('payment_method')
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // ===== Top Products (by revenue) =====
        $topProducts = SaleItem::select('product_id')
            ->whereHas('sale', fn ($query) => $query->where('status', 'completed'))
            ->selectRaw('SUM(quantity) as total_qty, SUM(quantity * price) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->with('product')
            ->take(10)
            ->get();

        $trackedItems = SaleItem::whereNotNull('unit_cost')
            ->whereHas('sale', fn ($query) => $query->where('status', 'completed'));
        $grossProfit = (float) (clone $trackedItems)
            ->selectRaw('SUM(quantity * price) - SUM(quantity * unit_cost) as gross_profit')
            ->value('gross_profit');
        $untrackedCostItems = SaleItem::whereNull('unit_cost')
            ->whereHas('sale', fn ($query) => $query->where('status', 'completed'))
            ->count();

        return view('reports.sales', compact(
            'salesByCategory',
            'monthLabels', 'monthData',
            'paymentBreakdown',
            'topProducts', 'grossProfit', 'untrackedCostItems'
        ));
    }
}
