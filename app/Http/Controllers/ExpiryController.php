<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Carbon\Carbon;

class ExpiryController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $nearLimit = Carbon::today()->addDays(15);

        $batches = Batch::with(['product.category'])
            ->where('status', '!=', 'depleted')
            ->orderByRaw('expiration_date IS NULL, expiration_date ASC')
            ->get();

        $expired = $batches->filter(function ($batch) use ($today) {
            return $batch->expiration_date && Carbon::parse($batch->expiration_date)->lt($today);
        });

        $nearExpiry = $batches->filter(function ($batch) use ($today, $nearLimit) {
            return $batch->expiration_date
                && Carbon::parse($batch->expiration_date)->gte($today)
                && Carbon::parse($batch->expiration_date)->lte($nearLimit);
        });

        $fresh = $batches->filter(function ($batch) use ($nearLimit) {
            return $batch->expiration_date && Carbon::parse($batch->expiration_date)->gt($nearLimit);
        });

        $noExpiry = $batches->filter(function ($batch) {
            return !$batch->expiration_date;
        });

        return view('expiry.index', compact('expired', 'nearExpiry', 'fresh', 'noExpiry'));
    }
}
