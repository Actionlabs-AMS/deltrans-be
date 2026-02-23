<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartsExpense extends Model
{
    use SoftDeletes;

    protected $table = 'parts_expense';

    protected $fillable = [
        'shift',
        'plate_number',
        'receipt_no',
        'quantity',
        'article',
        'amount_per_item',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount_per_item' => 'decimal:2',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function fleetTruck(): BelongsTo
    {
        return $this->belongsTo(FleetTruck::class, 'plate_number', 'plate_number');
    }
}
