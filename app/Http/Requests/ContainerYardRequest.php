<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ContainerYardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Define regex for reuse
        $numericDashRegex = 'regex:/^[0-9+ -]+$/';

        // Get the ID for unique rule ignoring
        $yardId = $this->route('id');

        return [
            // MIXED SYNTAX: string rules + unique Rule object                
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cypa_details', 'name')->ignore($yardId),
            ],

            // PIPE SYNTAX:
            'address' => 'required|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:20|' . $numericDashRegex,

            // PIPE SYNTAX for array validation:
            'landlines' => 'nullable|array',
            'landlines.*' => 'string|max:20|' . $numericDashRegex,

            'location_type' => 'required|string|max:50',

            // MIXED SYNTAX: string rules + in Rule object                
            'is_active' => [
                'required',
                'integer',
                Rule::in([0, 1]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This container yard name is already in use.',
            'contact_mobile.regex' => 'The mobile number may only contain numbers and dashes.',
            'landlines.*.regex' => 'Each landline number may only contain numbers and dashes.',
        ];
    }
}