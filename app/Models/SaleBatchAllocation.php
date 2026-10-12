<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleBatchAllocation extends Model
{
    protected $fillable = ['batch_id', 'quantity'];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
