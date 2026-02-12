<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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

      // Name
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'full_name' => $this->first_name . ' ' . $this->last_name,

      // Contact
      'contact_number' => $this->contact_number,

      // Status
      'is_active' => (int) $this->is_active,

      // Assignments
      'assigned_truck_plate_numbers' => $this->assigned_truck_plate_numbers ?? [],
      'stack_run' => $this->stack_run ?? [],
      'helper_id' => $this->helper_id,

      // Timestamps
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}

