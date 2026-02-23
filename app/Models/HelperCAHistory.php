<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelperCAHistory extends Model
{
    use SoftDeletes;

    protected $table = 'helper_cash_advancement_history';

    protected $fillable = [
        'amount',
        'transaction_date',
        'shift',
        'helper_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function helper(): BelongsTo
    {
        return $this->belongsTo(Helper::class, 'helper_id');
    }
}