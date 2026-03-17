<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransportDetailedResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'transaction_date'   => $this->transaction_date,
            'shipping_line'      => $this->shipping_line_name,
            'plate_number'       => $this->truck_plate_number,
            'waybill_number'     => $this->waybill_number,
            'container_number'   => $this->container_number ?? 'N/A',
            'from'               => $this->from,
            'to'                 => $this->to,
            'status'             => $this->status,
            'size'               => $this->container_size,
            'truck_expenses'     => (float) $this->truck_expense,
            'remarks'            => $this->remarks,
            'driver'             => $this->driver,
            'helper'             => $this->helper,
            'encoded_by'         => $this->encoded_by,
            'diesel_consumption' => $this->diesel_amount,
            'purchase_order'     => $this->purchase_order,
        ];
    }
}
