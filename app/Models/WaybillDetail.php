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
        'stack_run_id',
        'driver_id',
        'helper_id',
        'truck_plate_number',
        'fixed_expense_id',
        'rate_per_client_id',
        'extra_money',
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
        'extra_money' => 'decimal:2',
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
        'extra_money' => 0.00,
        'post_expense_amount' => 0.00,
        'total_rate_per_client' => 0.00,
        'total_expense' => 0.00,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total_rate_per_client before saving
        static::saving(function ($model) {
            // Calculate total_rate_per_client from rate_per_client relationship
            if ($model->rate_per_client_id) {
                if ($model->ratePerClient) {
                    $model->total_rate_per_client = $model->ratePerClient->rate + $model->ratePerClient->stack_run;
                } elseif ($model->isDirty('rate_per_client_id')) {
                    // If rate_per_client_id is being set but relationship not loaded, load it
                    $ratePerClient = \App\Models\RatePerClient::find($model->rate_per_client_id);
                    if ($ratePerClient) {
                        $model->total_rate_per_client = $ratePerClient->rate + $ratePerClient->stack_run;
                    } else {
                        $model->total_rate_per_client = 0.00;
                    }
                }
            } else {
                // If rate_per_client_id is null, set total_rate_per_client to 0
                $model->total_rate_per_client = 0.00;
            }

            // Calculate total_expense from extra_money + post_expense_amount + fixed_expense.total_expenses
            $fixedExpenseTotal = 0.00;
            if ($model->fixed_expense_id) {
                if ($model->fixedExpense) {
                    $fixedExpenseTotal = $model->fixedExpense->total_expenses;
                } elseif ($model->isDirty('fixed_expense_id')) {
                    // If fixed_expense_id is being set but relationship not loaded, load it
                    $fixedExpense = \App\Models\FixedExpense::find($model->fixed_expense_id);
                    if ($fixedExpense) {
                        $fixedExpenseTotal = $fixedExpense->total_expenses;
                    }
                }
            }

            $model->total_expense = ($model->extra_money ?? 0.00) + ($model->post_expense_amount ?? 0.00) + $fixedExpenseTotal;
        });
    }

    /**
     * Get the shipping line that owns the waybill detail.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    /**
     * Get the stack run that owns the waybill detail.
     */
    public function stackRun()
    {
        return $this->belongsTo(StackRun::class, 'stack_run_id');
    }

    /**
     * Get the driver that owns the waybill detail.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Get the helper that owns the waybill detail.
     */
    public function helper()
    {
        return $this->belongsTo(Helper::class, 'helper_id');
    }

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
}

