<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatePerClient extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipping_line_id',
        'no_of_days',
        'requirements',
        'remarks',
        'cypa_id',
        'stack_run',
        'container_size',
        'rate',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'no_of_days' => 'integer',
        'cypa_id' => 'integer',
        'stack_run' => 'decimal:2',
        'rate' => 'decimal:2',
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rate_per_clients';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => 1,
    ];

    /**
     * Get the shipping line that owns the rate per client.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the CYPA detail (cypa_id) that owns the rate per client.
     * Note: cypa_id = 0 means "all", so this relationship may return null.
     */
    public function cypa()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id');
    }

    /**
     * Get the waybill details for the rate per client.
     */
    public function waybillDetails()
    {
        return $this->hasMany(WaybillDetail::class, 'rate_per_client_id');
    }
}

