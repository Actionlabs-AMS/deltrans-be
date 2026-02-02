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
        // Calculate total amount from waybills (for SOA)
        $totalAmount = 0;
        $waybills = collect();

        if ($this->relationLoaded('booking') && $this->booking && $this->booking->relationLoaded('waybills')) {
            $waybills = $this->booking->waybills;
            $totalAmount = $waybills->sum('total_rate_per_client');
        } elseif ($this->relationLoaded('waybills')) {
            $waybills = $this->waybills;
            $totalAmount = $waybills->sum('total_rate_per_client');
        } elseif ($this->booking_id) {
            $waybills = \App\Models\WaybillDetail::where('booking_id', $this->booking_id)->get();
            $totalAmount = $waybills->sum('total_rate_per_client');
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
            'booking' => $this->whenLoaded('booking', function () {
                return new BookingResource($this->booking);
            }),
            'waybills' => $waybills->isNotEmpty()
                ? WaybillDetailResource::collection($waybills)
                : [],
            'total_amount' => (float) number_format($totalAmount, 2, '.', ''),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
