<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DieselExpense extends Model
{
    protected $fillable = [
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function waybillDetails(): HasMany
    {
        return $this->hasMany(WaybillDetail::class, 'diesel_expense_id');
    }
}
