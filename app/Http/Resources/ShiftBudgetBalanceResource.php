<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftBudgetBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when($this->id !== null, $this->id),
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'shift' => $this->shift,
            'issued_budget' => (float) $this->issued_budget,
            'carried_from_previous' => (float) $this->carried_from_previous,
            'total_budget' => (float) $this->total_budget,
            'total_expense' => (float) $this->total_expense,
            'remaining_coh' => (float) $this->remaining_coh,
            'cash_on_hand' => (float) $this->remaining_coh,
            'previous_shift_date' => $this->previous_shift_date?->format('Y-m-d'),
            'previous_shift' => $this->previous_shift,
            'computed_at' => $this->computed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s')),
            'updated_at' => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s')),
        ];
    }
}
