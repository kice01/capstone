<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $fillable = [
        'close_date',
        'closed_by',
        'expected_cash',
        'expected_gcash',
        'opening_float',
        'cash_expenses',
        'actual_cash',
        'actual_gcash',
        'notes',
        'closed_at',
    ];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime', 'close_date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
