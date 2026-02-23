<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckTripExpense extends Model
{
    use SoftDeletes;

    protected $table = 'truck_trip_expense';

    protected $fillable = [
        'shift',
        'helper_id',
        'cash_on_hand',
        'issued_cash_amount',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'cash_on_hand' => 'decimal:2',
        'issued_cash_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function helper(): BelongsTo
    {
        return $this->belongsTo(Helper::class, 'helper_id');
    }
}
