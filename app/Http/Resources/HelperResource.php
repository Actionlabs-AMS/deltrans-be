<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelperResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $rawPlates = $this->assigned_truck_plate_numbers ?? '';

    // Already an array (e.g. from getHelperById joining drivers)
    if (is_array($rawPlates)) {
      $platesArray = array_values(array_filter($rawPlates));
    } else {
      // String from DB: clean and split
      $cleaned = is_string($rawPlates) ? str_replace(['[', ']', '"'], '', $rawPlates) : '';
      $platesArray = !empty($cleaned)
        ? array_map('trim', explode(',', $cleaned))
        : [];
    }


    return [
      'id' => $this->id,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'full_name' => $this->first_name . ' ' . $this->last_name,
      'contact_number' => $this->contact_number,
      'is_active' => (int) $this->is_active,
      // This will now return ["DEF-9012", "GHI-3456", "JKL-7890"]
      'assigned_truck_plate_numbers' => $platesArray,
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}

