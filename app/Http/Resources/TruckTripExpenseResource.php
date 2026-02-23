<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TruckTripExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift' => $this->shift,
            'helper_id' => $this->helper_id,
            'helper_name' => $this->whenLoaded('helper', function () {
                return $this->helper ? trim($this->helper->first_name . ' ' . $this->helper->last_name) : null;
            }),
            'cash_on_hand' => (float) $this->cash_on_hand,
            'issued_cash_amount' => (float) $this->issued_cash_amount,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
