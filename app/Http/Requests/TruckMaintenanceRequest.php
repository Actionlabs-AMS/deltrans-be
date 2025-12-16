<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TruckMaintenanceRequest extends FormRequest
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
        return [
            'receipt_number' => [
                'required',
                'string',
                'max:255',
                // Assuming receipt numbers should be unique across all maintenance records
                'unique:truck_maintenance_records,receipt_number', 
            ],
            
            'article' => [
                'required',
                'string',
                'max:255',
            ],
            
            'quantity' => [
                'required',
                'integer',
                'min:1', // Quantity must be at least 1
            ],
            
            'price' => [
                'required',
                'numeric',
                'min:0.01', // Price must be a positive value
            ],
            
            'maintenance_date' => [
                'required',
                'date', // Ensures the value is a valid date format
                'before_or_equal:today', // Maintenance cannot be scheduled in the future
            ],
            
            'fleet_truck_plate_number' => [
                'required',
                'string',
                'max:255',
                // ✅ CRITICAL: Ensure the plate number exists in the main trucks table
                // Assuming your main truck table is named 'trucks' and the column is 'plate_number'
                'exists:fleet_trucks,plate_number', 
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt_number.unique' => 'This receipt number has already been recorded.',
            'quantity.min' => 'The quantity must be a positive number.',
            'price.min' => 'The price must be greater than zero.',
            'maintenance_date.before_or_equal' => 'The maintenance date cannot be set in the future.',
            'fleet_truck_plate_number.exists' => 'The provided truck plate number was not found in the fleet registry.',
        ];
    }
}
