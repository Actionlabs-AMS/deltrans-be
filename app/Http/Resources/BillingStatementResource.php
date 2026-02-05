<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingStatementResource extends JsonResource
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
            'shipping_line' => $this->whenLoaded('shippingLine', function () {
                return new ShippingLineResource($this->shippingLine);
            }),
            'booking_id' => $this->booking_id,
            'booking' => $this->whenLoaded('booking', function () {
                return new BookingResource($this->booking);
            }),
            'prepared_by' => $this->prepared_by,
            'prepared_by_user' => $this->whenLoaded('preparedByUser', function () {
                $user = $this->preparedByUser;
                $name = $user->name ?? null;
                if (!$name && isset($user->first_name)) {
                    $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                }
                return [
                    'id' => $user->id,
                    'name' => $name ?? '',
                ];
            }),
            'billing_statement_no' => $this->billing_statement_no,
            'payment_term' => $this->payment_term,
            'ci_date' => $this->ci_date ? $this->ci_date->format('Y-m-d') : null,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'bus_style' => $this->bus_style,
            'has_details' => (bool) $this->has_details,
            'is_paid' => (bool) $this->is_paid,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
