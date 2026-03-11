<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContainerYardResource extends JsonResource
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
            'name' => $this->name,
            'short_name' => $this->short_name,

            // Address / contact
            'address' => $this->address,
            'contact_name' => $this->contact_name,
            'contact_mobile' => $this->contact_mobile,
            'landlines' => $this->landlines ?? [],

            // Settings
            'location_type' => $this->location_type,
            'is_active' => (int) $this->is_active,

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}