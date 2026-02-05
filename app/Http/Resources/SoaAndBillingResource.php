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
        // Total amount from waybills (waybills live under booking only, no duplicate at SOA root)
        $totalAmount = 0;
        if ($this->relationLoaded('booking') && $this->booking && $this->booking->relationLoaded('waybills')) {
            $totalAmount = $this->booking->waybills->sum('total_rate_per_client');
        } elseif ($this->relationLoaded('waybills')) {
            $totalAmount = $this->waybills->sum('total_rate_per_client');
        } elseif ($this->booking_id) {
            $totalAmount = (float) \App\Models\WaybillDetail::where('booking_id', $this->booking_id)->sum('total_rate_per_client');
        }

        // Get vessel from booking
        $vessel = null;
        if ($this->relationLoaded('booking') && $this->booking) {
            $vessel = $this->booking->vessel;
        } elseif ($this->booking_id) {
            $booking = \App\Models\Booking::find($this->booking_id);
            $vessel = $booking ? $booking->vessel : null;
        }

        return [
            'id' => $this->id,
            'shipping_line_id' => $this->shipping_line_id,
            'shipping_line' => $this->whenLoaded('shippingLine', function () {
                return new ShippingLineResource($this->shippingLine);
            }),
            'dli_sa_number' => $this->dli_sa_number,
            'booking_id' => $this->booking_id,
            'work_order' => $this->work_order ?? null,
            'vessel' => $vessel,
            'booking' => $this->whenLoaded('booking', function () {
                return new BookingResource($this->booking);
            }),
            'total_amount' => (float) number_format($totalAmount, 2, '.', ''),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
