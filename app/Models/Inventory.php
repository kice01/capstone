<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'reason',
        'unit_cost',
        'batch_id',
        'supplier_id',
        'stock_in',
        'stock_out',
        'current_stock',
        'expiry_date',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batchAllocations()
    {
        return $this->hasMany(InventoryBatchAllocation::class);
    }
}
