<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
JsonResource::withoutWrapping();

class ReportsResource extends JsonResource
{
     /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'date'             => $this->date,
            'accounting_day'   => (float) ($this->accounting_day ?? 0),
            'accounting_night' => (float) ($this->accounting_night ?? 0),
            'truck_expense'    => (float) ($this->truck_expense ?? 0),
            'parts_expense'    => (float) ($this->parts_expense ?? 0),
            'bale_day'         => (float) ($this->bale_day ?? 0),
            'bale_night'       => (float) ($this->bale_night ?? 0),
            'total'            => (float) ($this->total ?? 0),
            'cash_on_hand'     => (float) ($this->cash_on_hand ?? 0),

            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}


