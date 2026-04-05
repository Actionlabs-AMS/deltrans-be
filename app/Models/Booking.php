<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'vessel',
        'shipping_line_id',
        'cypa_id_from',
        'cypa_id_to',
        'expected_date',
        'expected_container',
        'is_complete',
        'is_ship_in',
        'prepared_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expected_date' => 'date',
        'expected_container' => 'integer',
        'is_complete' => 'boolean',
        'is_ship_in' => 'boolean',
        'auto_complete_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bookings';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'expected_container' => 0,
        'is_complete' => false,
        'is_ship_in' => true,
    ];

    /**
     * Get the shipping line that owns the booking.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the CYPA detail (from) that owns the booking.
     */
    public function cypaFrom()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_from');
    }

    /**
     * Get the CYPA detail (to) that owns the booking.
     */
    public function cypaTo()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_to');
    }

    /**
     * Get the containers for the booking.
     */
    public function containers()
    {
        return $this->hasMany(Container::class, 'booking_id');
    }

    /**
     * Containers that count toward expected vs remaining capacity.
     *
     * Excludes rows tied only to a soft-deleted waybill so trashing a waybill restores remaining count;
     * unassigned (waybill_id null) and active-waybill rows are included.
     */
    public function activeBookingContainers()
    {
        return $this->hasMany(Container::class, 'booking_id')
            ->where(function ($q) {
                $q->whereNull('waybill_id')
                    ->orWhereHas('waybill');
            });
    }

    /**
     * Get the waybills for the booking.
     */
    public function waybills()
    {
        return $this->hasMany(WaybillDetail::class, 'booking_id');
    }

    /**
     * Get the user who prepared the booking.
     */
    public function preparedByUser()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
