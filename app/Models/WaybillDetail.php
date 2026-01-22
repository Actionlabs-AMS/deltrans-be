<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaybillDetail extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'waybill_number',
        'transaction_date',
        'shipping_line_id',
        'booking_id',
        'driver_id',
        'helper_id',
        'container_size',
        'truck_plate_number',
        'fixed_expense_id',
        'rate_per_client_id',
        'pickup_date',
        'delivered_date',
        'post_expense_amount',
        'total_rate_per_client',
        'total_expense',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_date' => 'date',
        'pickup_date' => 'date',
        'delivered_date' => 'date',
        'helper_id' => 'array', // JSON field cast to array
        'post_expense_amount' => 'decimal:2',
        'total_rate_per_client' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'waybill_details';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'post_expense_amount' => 0.00,
        'total_rate_per_client' => 0.00,
        'total_expense' => 0.00,
    ];

    // Removed auto-calculation logic - total_rate_per_client and total_expense are now manual inputs

    /**
     * Get the shipping line that owns the waybill detail.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the booking that owns the waybill detail.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the driver that owns the waybill detail.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // Removed helper() relationship - helper_id is now JSON array of helper IDs

    /**
     * Get the fleet truck that owns the waybill detail.
     */
    public function fleetTruck()
    {
        return $this->belongsTo(FleetTruck::class, 'truck_plate_number', 'plate_number');
    }

    /**
     * Get the fixed expense that owns the waybill detail.
     */
    public function fixedExpense()
    {
        return $this->belongsTo(FixedExpense::class, 'fixed_expense_id');
    }

    /**
     * Get the rate per client that owns the waybill detail.
     */
    public function ratePerClient()
    {
        return $this->belongsTo(RatePerClient::class, 'rate_per_client_id');
    }

    /**
     * Get the containers for the waybill detail.
     */
    public function containers()
    {
        return $this->hasMany(Container::class, 'waybill_id');
    }
}

