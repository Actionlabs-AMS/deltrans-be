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
            'id' => $this->id,
            'shipping_line_id' => $this->shipping_line_id,
            'no_of_days' => $this->no_of_days,
            'requirements' => $this->requirements,
            'remarks' => $this->remarks,
            'cypa_id' => $this->cypa_id,
            'stack_run' => $this->stack_run,
            'container_size' => $this->container_size,
            'rate' => $this->rate,
            'tax_percent' => $this->tax_percent,
            'has_vat' => (bool) $this->has_vat,
            'is_active' => (int) $this->is_active,
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'cypa' => $this->whenLoaded('cypa'),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

