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
        // Explicitly mapping the database columns to JSON keys
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'address'        => $this->address,
            'contact_name'   => $this->contact_name,
            'contact_mobile'   => $this->contact_mobile,
            'landlines'      => $this->landlines ?? [],
            'type'           => $this->type,
            'status'         => (int) $this->status, // Ensuring it's cast to integer
            'is_active'      => $this->status == 1,   // Adding a boolean helper
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,         
        ];
    }
}