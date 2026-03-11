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
            // Identity
            'id' => $this->id,

            // SOA reference
            'statement_of_account_id' => $this->statement_of_account_id,
            'statement_of_account' => $this->whenLoaded('statementOfAccount', function () {
                $soa = $this->statementOfAccount;
                return $soa ? [
                    'id' => $soa->id,
                    'dli_sa_number' => $soa->dli_sa_number,
                    'work_order' => $soa->work_order,
                    'booking_ids' => $soa->booking_ids ?? [],
                    'shipping_line_id' => $soa->shipping_line_id,
                ] : null;
            }),
            'shipping_line_id' => $this->statement_of_account_id ? $this->shipping_line_id : null,
            'booking_ids' => $this->statementOfAccount?->booking_ids ?? [],

            // Loaded relations
            'shipping_line' => $this->whenLoaded('shippingLine', function () {
                return $this->shippingLine ? new ShippingLineResource($this->shippingLine) : null;
            }),
            'booking' => $this->statement_of_account_id && $this->booking
                ? new BookingResource($this->booking)
                : null,

            // Prepared by: display name from users / user_meta (first_name, last_name, or user_login)
            'prepared_by' => $this->when($this->prepared_by, function () {
                $user = $this->preparedByUser;
                return $user ? $user->getDisplayName() : null;
            }),

            // Billing details
            'billing_statement_no' => $this->billing_statement_no,
            'payment_term' => $this->payment_term,
            'ci_date' => $this->ci_date ? $this->ci_date->format('Y-m-d') : null,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'bus_style' => $this->bus_style,
            'has_details' => (bool) $this->has_details,
            'is_paid' => (bool) $this->is_paid,

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
