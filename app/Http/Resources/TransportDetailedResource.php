<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransportDetailedResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'transaction_date'   => $this->transaction_date,
            'shipping_line'      => $this->shipping_line_name,
            'plate_number'       => $this->truck_plate_number,
            'waybill_number'     => $this->waybill_number,
            'container_number'   => $this->container_number ?? 'N/A',
            'route'              => "{$this->from} to {$this->to}",
            'status'             => $this->status,
            'size'               => $this->container_size,
            'expenses'           => (float) $this->total_expenses,
            'remarks'            => $this->remarks,
            'driver'             => $this->driver,
            'helper'             => $this->helper,
            'encoded_by'         => $this->encoded_by,
        ];
    }
}
