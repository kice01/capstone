<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Product;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.sidebar', 'layouts.topheader'], function ($view) {
            $today = Carbon::today();

            $expired = Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', $today)
                ->orderBy('expiry_date')
                ->get();

            $within7 = Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(7))
                ->orderBy('expiry_date')
                ->get();

            $within15 = Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>', $today->copy()->addDays(7))
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(15))
                ->orderBy('expiry_date')
                ->get();

            $within30 = Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>', $today->copy()->addDays(15))
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(30))
                ->orderBy('expiry_date')
                ->get();

            $notifCount = $expired->count() + $within7->count() + $within15->count() + $within30->count();

            $view->with([
                'notifExpired' => $expired,
                'notifWithin7' => $within7,
                'notifWithin15' => $within15,
                'notifWithin30' => $within30,
                'notifCount' => $notifCount,
            ]);
        });
    }
}
