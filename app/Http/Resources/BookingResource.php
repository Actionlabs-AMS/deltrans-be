<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'vessel' => $this->vessel,
            'shipping_line_id' => $this->shipping_line_id,
            'cypa_id_from' => $this->cypa_id_from,
            'cypa_id_to' => $this->cypa_id_to,
            'expected_date' => $this->expected_date ? $this->expected_date->format('Y-m-d') : null,
            'is_complete' => (bool) $this->is_complete,
            'actual_no_of_waybill' => isset($this->actual_no_of_waybill) ? (int) $this->actual_no_of_waybill : 0,
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'cypa_from' => $this->whenLoaded('cypaFrom'),
            'cypa_to' => $this->whenLoaded('cypaTo'),
            'containers' => $this->whenLoaded('containers'),
            'waybills' => $this->whenLoaded('waybills'),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
