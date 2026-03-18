<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportSummaryResource extends JsonResource
{
   public function toArray($request)
    {
        return [
            'truck_plate_number' => $this->truck_plate_number,
            'total_trips'        => $this->total_trips,
            'total_expenses'     => (float) ($this->total_expenses ?? 0),
        ];
    }
}
