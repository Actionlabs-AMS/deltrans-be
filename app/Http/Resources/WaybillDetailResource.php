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
            'stack_run_id' => $this->stack_run_id,
            'driver_id' => $this->driver_id,
            'helper_id' => $this->helper_id,
            'truck_plate_number' => $this->truck_plate_number,
            'fixed_expense_id' => $this->fixed_expense_id,
            'rate_per_client_id' => $this->rate_per_client_id,
            'extra_money' => $this->extra_money,
            'pickup_date' => $this->pickup_date ? $this->pickup_date->format('Y-m-d') : null,
            'delivered_date' => $this->delivered_date ? $this->delivered_date->format('Y-m-d') : null,
            'post_expense_amount' => $this->post_expense_amount,
            'total_rate_per_client' => $this->total_rate_per_client,
            'total_expense' => $this->total_expense,
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'stack_run' => $this->whenLoaded('stackRun'),
            'driver' => $this->whenLoaded('driver'),
            'helper' => $this->whenLoaded('helper'),
            'fleet_truck' => $this->whenLoaded('fleetTruck'),
            'fixed_expense' => $this->whenLoaded('fixedExpense'),
            'rate_per_client' => $this->whenLoaded('ratePerClient'),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

