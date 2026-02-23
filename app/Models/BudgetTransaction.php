<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'budget_transactions';

    const TYPE_ADD_BUDGET = 0;
    const TYPE_TRUCK_TRIP_EXPENSE = 1;
    const TYPE_PARTS_EXPENSE = 2;
    const TYPE_FUNDS_FOR_STACK_RUN = 3;
    const TYPE_ADVANCE_EXPENSE = 4;

    const SHIFT_MORNING = 0;
    const SHIFT_NIGHT = 1;

    protected $fillable = [
        'shift',
        'transaction_type',
        'description',
    ];

    protected $casts = [
        'shift' => 'integer',
        'transaction_type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
