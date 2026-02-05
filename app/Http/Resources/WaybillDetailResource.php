<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaybillDetailResource extends JsonResource
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
            'waybill_number' => $this->waybill_number,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->format('Y-m-d') : null,
            'shipping_line_id' => $this->shipping_line_id,
            'booking_id' => $this->booking_id,
            'driver_id' => $this->driver_id,
            'helper_id' => $this->helper_id,
            'helper' => $this->whenLoaded('helper'),
            'container_size' => $this->container_size,
            'container_type' => $this->container_type,
            'truck_plate_number' => $this->truck_plate_number,
            'pickup_date' => $this->pickup_date ? $this->pickup_date->format('Y-m-d') : null,
            'delivered_date' => $this->delivered_date ? $this->delivered_date->format('Y-m-d') : null,
            'no_of_days' => $this->no_of_days,
            'requirements' => $this->requirements,
            'remarks' => $this->remarks,
            'stack_run' => $this->stack_run,
            'rate' => $this->rate,
            'tax_percent' => $this->tax_percent,
            'has_vat' => $this->has_vat,
            'total_rate_per_client' => $this->total_rate_per_client,
            'fixed_expense_id' => $this->fixed_expense_id,
            'post_expense_amount' => $this->post_expense_amount,
            'total_expense' => $this->total_expense,
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'booking' => $this->whenLoaded('booking'),
            'driver' => $this->whenLoaded('driver'),
            'fleet_truck' => $this->whenLoaded('fleetTruck'),
            'fixed_expense' => $this->whenLoaded('fixedExpense'),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

