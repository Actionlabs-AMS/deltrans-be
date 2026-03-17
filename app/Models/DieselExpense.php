<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DieselExpense extends Model
{
    protected $fillable = [
        'amount',
        'purchase_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function waybillDetail(): HasOne
    {
        return $this->hasOne(WaybillDetail::class, 'diesel_expense_id');
    }
}
