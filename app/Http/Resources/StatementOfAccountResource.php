<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatementOfAccountResource extends JsonResource
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
            'transaction_number' => $this->transaction_number,
            'shipping_line_id' => $this->shipping_line_id,
            'shipping_line' => $this->whenLoaded('shippingLine', function () {
                return [
                    'id' => $this->shippingLine->id,
                    'name' => $this->shippingLine->name,
                ];
            }),
            'dli_sa_number' => $this->dli_sa_number,
            'soa_coverage_from' => $this->soa_coverage_from ? $this->soa_coverage_from->format('Y-m-d') : null,
            'soa_coverage_to' => $this->soa_coverage_to ? $this->soa_coverage_to->format('Y-m-d') : null,
            'waybill_id' => $this->waybill_id ?? [],
            'signature' => (bool) $this->signature,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
