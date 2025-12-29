<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Recommended for deleted_at

class TruckMaintenance extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes trait

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fleet_truck_maintenance_history';

    protected $fillable = [
        'receipt_number',
        'article',
        'quantity',
        'price',
        'maintenance_date',
        'fleet_truck_plate_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'maintenance_date' => 'date', // Cast maintenance date as a date object
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Get the truck that this maintenance record belongs to.
     * Assumes the Truck model uses 'plate_number' as its key.
     */
    public function truck()
    {
        return $this->belongsTo(Truck::class, 'fleet_truck_plate_number', 'plate_number');
    }

    // --- ACCESSORS ---
    
    /**
     * Get the total cost of the maintenance record (quantity * price).
     *
     * @return float
     */
    protected function getTotalCostAttribute()
    {
        return $this->quantity * $this->price;
    }
}