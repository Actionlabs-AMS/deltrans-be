<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Unified cash advance resource. Supports both driver (type=1) and helper (type=2) records.
 * The resource instance can be DriverCAHistory or HelperCAHistory; type is inferred from driver_id/helper_id.
 */
class CashAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDriver = isset($this->driver_id);

        return [
            'type' => $isDriver ? 1 : 2,
            'id' => $this->id,
            'amount' => (float) $this->amount,
            //'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            //'transaction_date_formatted' => $this->transaction_date?->format('F d, Y'),
            'transaction_date' => $this->transaction_date 
                ? Carbon::parse($this->transaction_date)->format('Y-m-d') 
                : null,
            'transaction_date_formatted' => $this->transaction_date 
                ? Carbon::parse($this->transaction_date)->format('F d, Y') 
                : null,
            'shift' => $this->shift,
            'driver_id' => $this->driver_id ?? null,
            // 'driver_name' => $this->when(isset($this->driver_id) && $this->relationLoaded('drivers'), function () {
            //     return $this->driver ? trim($this->driver->first_name . ' ' . $this->driver->last_name) : null;
            // }),
            'driver_name' => $this->driver ? trim(($this->driver->first_name ?? '') . ' ' . ($this->driver->last_name ?? '')) : null,
            'helper_id' => $this->helper_id ?? null,
            // 'helper_name' => $this->when(isset($this->helper_id) && $this->relationLoaded('helpers'), function () {
            //     return $this->helper ? trim($this->helper->first_name . ' ' . $this->helper->last_name) : null;
            // }),
            'helper_name' => $this->helper ? trim(($this->helper->first_name ?? '') . ' ' . ($this->helper->last_name ?? '')) : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
