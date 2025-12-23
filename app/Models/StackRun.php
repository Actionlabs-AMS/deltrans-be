<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StackRun extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'shipping_line_id',
        'cypa_id_from',
        'cypa_id_to',
        'quantity_of_container',
        'container_size',
        'status',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_of_container' => 'integer',
        'status' => 'integer',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stack_runs';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 0,
        'total_amount' => 0.00,
    ];

    /**
     * Get the shipping line that owns the stack run.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the CYPA detail (from) that owns the stack run.
     */
    public function cypaFrom()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_from');
    }

    /**
     * Get the CYPA detail (to) that owns the stack run.
     */
    public function cypaTo()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_to');
    }

    /**
     * Get the containers for the stack run.
     */
    public function containers()
    {
        return $this->hasMany(Container::class, 'stack_run_id');
    }
}


