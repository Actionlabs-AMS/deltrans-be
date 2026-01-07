<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TruckMaintenanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'article' => $this->article,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'maintenance_date' => $this->maintenance_date,
            'fleet_truck_plate_number' => $this->fleet_truck_plate_number,
            'created_at'     => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null, // Line 28 is likely here    
            'updated_at'     => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
            'truck_id' => $this->truck_id,
        ];
    }
}
