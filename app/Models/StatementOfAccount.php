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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipping_line_id',
        'dli_sa_number',
        'booking_ids',
        'work_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shipping_line_id' => 'integer',
        'booking_ids' => 'array',
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

    /**
     * Get the first booking ID (backward compatibility).
     */
    public function getBookingIdAttribute(): ?int
    {
        $ids = $this->booking_ids ?? [];
        return is_array($ids) && count($ids) > 0 ? (int) $ids[0] : null;
    }

    /**
     * Get the bookings associated with the statement of account (by booking_ids).
     * Returns a query; use get() to execute. For eager loading, setRelation('bookings', ...) in the service.
     */
    public function bookings()
    {
        $ids = $this->booking_ids ?? [];
        return empty($ids) ? Booking::whereRaw('1 = 0') : Booking::whereIn('id', $ids);
    }

    /**
     * Get all waybills for the SOA (across all booking_ids).
     */
    public function waybills()
    {
        $ids = $this->booking_ids ?? [];
        if (empty($ids)) {
            return WaybillDetail::whereRaw('1 = 0');
        }
        return WaybillDetail::whereIn('booking_id', $ids);
    }
}
