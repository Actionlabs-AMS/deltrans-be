<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverCAHistory extends Model
{
    use SoftDeletes;

    protected $table = 'driver_cash_advancement_history';

    protected $fillable = [
        'amount',
        'transaction_date',
        'shift',
        'driver_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}