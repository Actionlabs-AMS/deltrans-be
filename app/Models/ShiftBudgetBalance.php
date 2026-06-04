<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftBudgetBalance extends Model
{
    protected $table = 'shift_budget_balances';

    protected $fillable = [
        'transaction_date',
        'shift',
        'issued_budget',
        'carried_from_previous',
        'total_budget',
        'total_expense',
        'remaining_coh',
        'previous_shift_date',
        'previous_shift',
        'computed_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'previous_shift_date' => 'date',
        'issued_budget' => 'decimal:2',
        'carried_from_previous' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'remaining_coh' => 'decimal:2',
        'computed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
