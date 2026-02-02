<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelperCAHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'transaction_date_formatted' => $this->transaction_date->format('F d, Y'),
            'shift' => $this->shift,
            'helper_id' => $this->helper_id,
            
            // Helpful if you want to show the helper name without another API call
            'helper_name' => $this->whenLoaded('helpers', function() {
                return $this->helper->first_name . ' ' . $this->helper->last_name;
            }),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null, // Line 28 is likely here    
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
