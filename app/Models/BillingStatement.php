<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'shipping_line_id',
        'booking_id',
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
        'shipping_line_id' => 'integer',
        'booking_id' => 'integer',
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
     * Get the shipping line that owns the billing statement.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the booking associated with the billing statement.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the user who prepared the billing statement.
     */
    public function preparedByUser()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
