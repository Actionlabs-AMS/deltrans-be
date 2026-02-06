<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StatementOfAccount;

class BillingStatement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'billing_statements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'statement_of_account_id',
        'prepared_by',
        'billing_statement_no',
        'payment_term',
        'ci_date',
        'due_date',
        'bus_style',
        'has_details',
        'is_paid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'statement_of_account_id' => 'integer',
        'prepared_by' => 'integer',
        'ci_date' => 'date',
        'due_date' => 'date',
        'has_details' => 'boolean',
        'is_paid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the statement of account associated with the billing statement.
     */
    public function statementOfAccount()
    {
        return $this->belongsTo(StatementOfAccount::class, 'statement_of_account_id');
    }

    /**
     * Get the shipping line via the statement of account.
     */
    public function shippingLine()
    {
        return $this->hasOneThrough(
            ShippingLine::class,
            StatementOfAccount::class,
            'id',
            'id',
            'statement_of_account_id',
            'shipping_line_id'
        );
    }

    /**
     * Get the booking via the statement of account.
     */
    public function booking()
    {
        return $this->hasOneThrough(
            Booking::class,
            StatementOfAccount::class,
            'id',
            'id',
            'statement_of_account_id',
            'booking_id'
        );
    }

    /**
     * Shipping line ID from the related statement of account.
     */
    public function getShippingLineIdAttribute(): ?int
    {
        return $this->statementOfAccount?->shipping_line_id;
    }

    /**
     * Booking ID from the related statement of account.
     */
    public function getBookingIdAttribute(): ?int
    {
        return $this->statementOfAccount?->booking_id;
    }

    /**
     * Get the user who prepared the billing statement.
     */
    public function preparedByUser()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
