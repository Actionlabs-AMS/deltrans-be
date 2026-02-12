<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverCAHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identity
            'id' => $this->id,

            // Amount / date
            'amount' => (float) $this->amount,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'transaction_date_formatted' => $this->transaction_date->format('F d, Y'),

            // Details
            'shift' => $this->shift,

            // Reference
            'driver_id' => $this->driver_id,

            // Loaded relation
            'driver_name' => $this->whenLoaded('driver', function () {
                return $this->driver->first_name . ' ' . $this->driver->last_name;
            }),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
