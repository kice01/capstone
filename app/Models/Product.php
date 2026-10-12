<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{ 
  protected $fillable = [
       'category_id',
       'name',
       'size',
       'unit',
       'barcode',
       'quantity',
       'price',
       'expiry_date',
];

public function category()
{
return $this->belongsTo(Category::class);
}

public function getDisplayNameAttribute(): string
{
    return trim($this->name . ($this->size ? ' ' . $this->size : '') . ($this->unit ? ' ' . $this->unit : ''));
}

public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
