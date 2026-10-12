<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpiryReportController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $nearLimit = Carbon::today()->addDays(15);

        // ===== Expiry Status Breakdown =====
        $expiredCount = Product::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)->count();

        $nearExpiryCount = Product::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $nearLimit)->count();

        $safeCount = Product::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>', $nearLimit)->count();

        $noExpiryCount = Product::whereNull('expiry_date')->count();

        // ===== Expiry by Category (near-expiry + expired only) =====
        $expiryByCategory = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category')
            ->selectRaw("SUM(CASE WHEN products.expiry_date < ? THEN 1 ELSE 0 END) as expired", [$today])
            ->selectRaw("SUM(CASE WHEN products.expiry_date >= ? AND products.expiry_date <= ? THEN 1 ELSE 0 END) as near_expiry", [$today, $nearLimit])
            ->whereNotNull('products.expiry_date')
            ->groupBy('categories.name')
            ->having(DB::raw('expired + near_expiry'), '>', 0)
            ->get();

        // ===== Expiring Soon (next 15 days) =====
        $expiringSoon = Product::with('category')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $nearLimit)
            ->orderBy('expiry_date')
            ->get();

        // ===== Expired Products =====
        $expiredProducts = Product::with('category')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->orderBy('expiry_date', 'desc')
            ->get();

        return view('reports.expiry', compact(
            'expiredCount', 'nearExpiryCount', 'safeCount', 'noExpiryCount',
            'expiryByCategory',
            'expiringSoon',
            'expiredProducts'
        ));
    }
}
