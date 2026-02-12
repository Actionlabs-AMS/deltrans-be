<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedExpense extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipping_line_id',
        'cypa_id_from',
        'cypa_id_to',
        'container_size',
        'docs_fee',
        'online_booking_fee',
        'stack_run',
        'expenses',
        'total_expenses',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'docs_fee' => 'decimal:2',
        'online_booking_fee' => 'decimal:2',
        'stack_run' => 'decimal:2',
        'expenses' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fixed_expenses';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'docs_fee' => 0.00,
        'online_booking_fee' => 0.00,
        'stack_run' => 0.00,
        'expenses' => 0.00,
        'total_expenses' => 0.00,
    ];

    /**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
        'shipping_line_name',
        'cypa_from_name',
        'cypa_to_name',
	];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total_expenses before saving
        static::saving(function ($model) {
            $model->total_expenses = (float) $model->docs_fee + (float) $model->online_booking_fee + (float) $model->stack_run + (float) $model->expenses;
        });
    }

    /**
     * Get the shipping line that owns the fixed expense.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the CYPA detail (from) that owns the fixed expense.
     */
    public function cypaFrom()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_from');
    }

    /**
     * Get the CYPA detail (to) that owns the fixed expense.
     */
    public function cypaTo()
    {
        return $this->belongsTo(ContainerYard::class, 'cypa_id_to');
    }

    /****************************************
	*           ATTRIBUTES PARTS            *
	****************************************/
    public function getShippingLineNameAttribute()
    {
        return $this->shippingLine?->name;
    }

    public function getCypaFromNameAttribute()
    {
        return $this->cypaFrom?->name;
    }

    public function getCypaToNameAttribute()
    {   
        return $this->cypaTo?->name;
    }

    /**
     * Get the waybill details for the fixed expense.
     */
    public function waybillDetails()
    {
        return $this->hasMany(WaybillDetail::class, 'fixed_expense_id');
    }
}

