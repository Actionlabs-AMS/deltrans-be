<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'container_number',
        'stack_run_id',
        'waybill_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'stack_run_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'containers';

    /**
     * Get the stack run that owns the container.
     */
    public function stackRun()
    {
        return $this->belongsTo(StackRun::class, 'stack_run_id');
    }

    /**
     * Get the waybill detail associated with the container.
     * Note: Uncomment when WaybillDetail model is created
     */
    // public function waybill()
    // {
    //     return $this->belongsTo(\App\Models\WaybillDetail::class, 'waybill_number', 'waybill_number');
    // }
}
