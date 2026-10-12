<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
protected $fillable = [
        'customer_name',
        'user_id',
        'voided_by',
        'voided_at',
        'void_reason',
        'status',
        'total_amount',
        'sale_date',
        'payment_method',
        'cash_tendered',
        'change_amount',
    ];

    protected function casts(): array
    {
        return ['voided_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
