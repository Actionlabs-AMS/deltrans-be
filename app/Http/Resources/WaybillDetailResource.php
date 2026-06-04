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
            // Identity
            'id' => $this->id,
            'waybill_number' => $this->waybill_number,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->format('Y-m-d') : null,

            // Reference IDs
            'shipping_line_id' => $this->shipping_line_id,
            // Prepared by: display name from users / user_meta (GET returns name; POST/PUT use ID)
            'prepared_by' => $this->when($this->prepared_by, function () {
                $user = $this->preparedByUser;
                return $user ? $user->getDisplayName() : null;
            }),

            'booking_id' => $this->booking_id,
            'driver_id' => $this->driver_id,
            'helper_id' => $this->helper_id,
            'fixed_expense_id' => $this->fixed_expense_id,
            'truck_trip_expense_id' => $this->truck_trip_expense_id,
            'diesel_expense_id' => $this->diesel_expense_id,

            // Container & truck
            'container_size' => $this->container_size,
            'container_type' => $this->container_type,
            'container_numbers' => $this->whenLoaded(
                'containers',
                fn () => $this->containers->pluck('container_number')->values()->all(),
                []
            ),
            'containers' => $this->whenLoaded('containers', function () {
                return $this->containers->map(fn ($container) => [
                    'id' => $container->id,
                    'container_number' => $container->container_number,
                ])->values()->all();
            }),
            'truck_plate_number' => $this->truck_plate_number,

            // Trip dates
            'pickup_date' => $this->pickup_date ? $this->pickup_date->format('Y-m-d') : null,
            'delivered_date' => $this->delivered_date ? $this->delivered_date->format('Y-m-d') : null,
            'no_of_days' => $this->no_of_days,

            // Notes
            'requirements' => $this->requirements,
            'remarks' => $this->remarks,

            // Rates (client)
            'rate' => $this->rate,
            'tax_percent' => $this->tax_percent,
            'has_vat' => $this->has_vat,
            'total_rate_per_client' => $this->total_rate_per_client,

            // Stack run: rate from waybill_details, fixed from fixed_expenses
            'stack_run_rate' => $this->stack_run,
            'stack_run_fixed' => $this->whenLoaded('fixedExpense', fn () => $this->fixedExpense?->stack_run),
            // Fixed expense (from linked fixed expense when loaded)
            'docs_fee' => $this->whenLoaded('fixedExpense', fn () => $this->fixedExpense?->docs_fee),
            'online_booking_fee' => $this->whenLoaded('fixedExpense', fn () => $this->fixedExpense?->online_booking_fee),
            'expenses' => $this->whenLoaded('fixedExpense', fn () => $this->fixedExpense?->expenses),
            'post_expense_amount' => $this->post_expense_amount,
            'actual_truck_trip_expense_amount' => $this->actual_truck_trip_expense_amount,
            'diesel_expense_amount' => $this->whenLoaded('dieselExpense', fn () => $this->dieselExpense?->amount),
            'purchase_order' => $this->whenLoaded('dieselExpense', fn () => $this->dieselExpense?->purchase_order),
            'total_expense' => $this->total_expense,

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine'),
            'booking' => $this->whenLoaded('booking'),
            'driver' => $this->whenLoaded('driver'),
            'helper' => $this->whenLoaded('helper'),
            'fleet_truck' => $this->whenLoaded('fleetTruck'),
            'fixed_expense' => $this->whenLoaded('fixedExpense'),
            'truck_trip_expense' => $this->whenLoaded('truckTripExpense'),
            'diesel_expense' => $this->whenLoaded('dieselExpense'),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}

