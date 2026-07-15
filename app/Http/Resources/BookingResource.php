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
        $expectedContainer = (int) ($this->expected_container ?? 0);
        $containersCount = isset($this->containers_count)
            ? (int) $this->containers_count
            : (int) $this->resource->activeBookingContainers()->count();

        return [
            // Identity
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'vessel' => $this->vessel,

            // Reference IDs
            'shipping_line_id' => $this->shipping_line_id,
            'cypa_id_from' => $this->cypa_id_from,
            'cypa_id_to' => $this->cypa_id_to,

            // Dates / status
            'expected_date' => $this->expected_date ? $this->expected_date->format('Y-m-d') : null,
            'expected_container' => $expectedContainer,
            'containers_count' => $containersCount,
            'remaining_container' => $expectedContainer - $containersCount,
            'is_complete' => (bool) $this->is_complete,
            'is_ship_in' => (bool) $this->is_ship_in,
            'actual_no_of_waybill' => isset($this->actual_no_of_waybill) ? (int) $this->actual_no_of_waybill : 0,

            // SOA tagging (present on bookings/by-shipping-line when resolved server-side)
            'has_soa' => $this->when(
                array_key_exists('has_soa', $this->resource->getAttributes()),
                (bool) $this->has_soa
            ),
            'soa_id' => $this->when(
                array_key_exists('has_soa', $this->resource->getAttributes()),
                $this->soa_id !== null ? (int) $this->soa_id : null
            ),
            'soa_dli_sa_number' => $this->when(
                array_key_exists('has_soa', $this->resource->getAttributes()),
                $this->soa_dli_sa_number
            ),

            // Prepared by: display name from users / user_meta (GET returns name; POST/PUT use ID)
            'prepared_by' => $this->when($this->prepared_by, function () {
                $user = $this->preparedByUser;
                return $user ? $user->getDisplayName() : null;
            }),

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'cypa_from' => $this->whenLoaded('cypaFrom'),
            'cypa_to' => $this->whenLoaded('cypaTo'),
            'containers' => $this->whenLoaded('containers'),
            'waybills' => $this->whenLoaded('waybills', function () {
                return WaybillDetailResource::collection($this->waybills);
            }),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
