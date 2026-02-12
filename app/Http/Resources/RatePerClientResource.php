<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatePerClientResource extends JsonResource
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

            // Reference IDs
            'shipping_line_id' => $this->shipping_line_id,
            'cypa_id' => $this->cypa_id,

            // Trip / options
            'no_of_days' => $this->no_of_days,
            'requirements' => $this->requirements,
            'remarks' => $this->remarks,

            // Rates
            'stack_run' => $this->stack_run,
            'container_size' => $this->container_size,
            'rate' => $this->rate,
            'tax_percent' => $this->tax_percent,
            'has_vat' => (bool) $this->has_vat,

            // Flags
            'is_active' => (int) $this->is_active,

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'cypa' => $this->whenLoaded('cypa'),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

