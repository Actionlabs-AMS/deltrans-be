<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TruckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $truckId = $this->route('id');

        // --- TEMPORARY DEBUGGING LINE ---
        // Stop execution and show the ID being used.
        // If this outputs null, 0, or 'undefined', the routing parameter name is wrong.
        //dd($truckId); 
        // --- END DEBUGGING LINE ---

        return [
            //'plate_number' => 'required|string|max:255|unique:fleet_trucks,plate_number,' . $this->id,
            'plate_number' => [
                'required',
                'string',
                'max:255',
                
                // 3. APPLY THE EXCEPTION FOR THE CURRENT RECORD
                Rule::unique('fleet_trucks', 'plate_number')
                    ->ignore($truckId, 'id')
                    ->whereNull('deleted_at')
            ],
            
            'condition' => 'required|string|max:255',
            'is_active' => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate_number.required' => 'The truck plate number is required.',
            'plate_number.unique' => 'This truck plate number is already taken.',
        ];
    }
}
