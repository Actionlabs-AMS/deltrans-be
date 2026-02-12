<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixedExpenseResource extends JsonResource
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
            'cypa_id_from' => $this->cypa_id_from,
            'cypa_id_to' => $this->cypa_id_to,

            // Container
            'container_size' => $this->container_size,

            // Expense amounts
            'docs_fee' => $this->docs_fee,
            'online_booking_fee' => $this->online_booking_fee,
            'stack_run' => $this->stack_run,
            'expenses' => $this->expenses,
            'total_expenses' => $this->total_expenses,

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'cypa_from' => $this->whenLoaded('cypaFrom'),
            'cypa_to' => $this->whenLoaded('cypaTo'),

            // Related names
            'shipping_line_name' => $this->shippingLine?->name,
            'cypa_from_name' => $this->cypaFrom?->name,
            'cypa_to_name' => $this->cypaTo?->name,

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

