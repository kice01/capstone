<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'quantity',
        'expiration_date',
        'date_received',
        'status',
        'source',
        'unit_cost',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Deduct quantity from active batches of a product, earliest expiry first (FEFO).
     * Returns the exact batch quantities allocated to the movement, or fails atomically.
     */
    public static function deductFefo(int $productId, int $qtyToDeduct, bool $excludeExpired = false): array
    {
        $query = self::where('product_id', $productId)
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->orderByRaw('expiration_date IS NULL, expiration_date ASC')
            ->orderBy('id');

        if ($excludeExpired) {
            $query->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhereDate('expiration_date', '>=', Carbon::today());
            });
        }

        $batches = $query->lockForUpdate()->get();
        $available = (int) $batches->sum('quantity');

        if ($available < $qtyToDeduct) {
            throw new RuntimeException("Insufficient batch stock. Available: {$available}.");
        }

        $remaining = $qtyToDeduct;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $take = min($batch->quantity, $remaining);
            $batch->quantity -= $take;
            $batch->status = $batch->quantity <= 0 ? 'depleted' : 'active';
            $batch->save();

            $remaining -= $take;
            $allocations[$batch->id] = $take;
        }

        return $allocations;
    }

    /**
     * Recalculate and sync the parent product's cached quantity from active batches.
     */
    public static function syncProductQuantity(int $productId): void
    {
        $batches = self::where('product_id', $productId)
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->get(['quantity', 'expiration_date']);

        $total = (int) $batches->sum('quantity');

        $earliestBatch = $batches
            ->sortBy(fn ($batch) => $batch->expiration_date ? '0-'.$batch->expiration_date : '1')
            ->first();

        Product::where('id', $productId)->update([
            'quantity' => $total,
            'expiry_date' => $earliestBatch?->expiration_date,
        ]);
    }
}
