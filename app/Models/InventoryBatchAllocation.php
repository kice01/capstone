<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBatchAllocation extends Model
{
    protected $fillable = ['batch_id', 'quantity'];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
