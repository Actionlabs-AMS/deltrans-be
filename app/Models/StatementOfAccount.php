<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatementOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statement_of_accounts';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($soa) {
            if (empty($soa->transaction_number)) {
                $soa->transaction_number = static::generateTransactionNumber();
            }
        });
    }

    /**
     * Generate a unique transaction number.
     *
     * @return string
     */
    public static function generateTransactionNumber(): string
    {
        $year = date('Y');
        $prefix = 'SOA-' . $year . '-';

        // Get the last transaction number for this year
        $lastSoa = static::where('transaction_number', 'like', $prefix . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastSoa) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastSoa->transaction_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_number',
        'shipping_line_id',
        'dli_sa_number',
        'soa_coverage_from',
        'soa_coverage_to',
        'waybill_id',
        'signature',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shipping_line_id' => 'integer',
        'soa_coverage_from' => 'date',
        'soa_coverage_to' => 'date',
        'waybill_id' => 'array',
        'signature' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the shipping line that owns the statement of account.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }
}
