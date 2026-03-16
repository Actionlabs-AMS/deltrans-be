<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DieselExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'purchase_order' => $this->purchase_order,
            'waybill_detail_id' => $this->whenLoaded('waybillDetail', function () {
                return $this->waybillDetail?->id;
            }),
            'waybill_number' => $this->whenLoaded('waybillDetail', function () {
                return $this->waybillDetail?->waybill_number;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
