<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoaAndBillingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Total amount from waybills (across all booking_ids)
        $totalAmount = 0;
        if ($this->relationLoaded('waybills')) {
            $totalAmount = $this->waybills->sum('total_rate_per_client');
        } else {
            $ids = $this->booking_ids ?? [];
            if (!empty($ids)) {
                $totalAmount = (float) \App\Models\WaybillDetail::whereIn('booking_id', $ids)->sum('total_rate_per_client');
            }
        }

        // Get vessel from first booking (backward compatibility)
        $vessel = null;
        if ($this->relationLoaded('bookings') && $this->bookings->isNotEmpty()) {
            $vessel = $this->bookings->first()->vessel;
        } elseif (!empty($this->booking_ids)) {
            $first = \App\Models\Booking::find($this->booking_ids[0] ?? null);
            $vessel = $first ? $first->vessel : null;
        }

        return [
            // Identity
            'id' => $this->id,

            // Reference IDs
            'shipping_line_id' => $this->shipping_line_id,

            // SOA fields
            'dli_sa_number' => $this->dli_sa_number,
            'booking_ids' => $this->booking_ids ?? [],
            'booking_id' => $this->booking_id,
            'work_order' => $this->work_order ?? null,

            // Computed (from waybills / first booking)
            'vessel' => $vessel,
            'total_amount' => (float) number_format($totalAmount, 2, '.', ''),

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine', function () {
                return new ShippingLineResource($this->shippingLine);
            }),
            'bookings' => $this->whenLoaded('bookings', function () {
                return BookingResource::collection($this->bookings);
            }),
            'booking' => $this->whenLoaded('bookings', function () {
                return $this->bookings->isNotEmpty() ? new BookingResource($this->bookings->first()) : null;
            }),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
