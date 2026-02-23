<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HelperCAHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Rules for fetching history (GET)
        if ($this->isMethod('get')) {
            return [
                'id' => 'required|integer|exists:drivers,id',
                'search' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ];
        }

        // Rules for creating/updating (POST/PUT)
        return [
            'helper_id' => 'required|integer|exists:helpers,id',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'transaction_date' => 'required|date|before_or_equal:today',
            'shift' => [
                'required',
                'string',
                Rule::in(['Day', 'Night', '1st', '2nd']), // Adjust based on your business logic
            ],
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // If it's a GET request, we take the ID from the route path
        if ($this->isMethod('get')) {
            $this->merge([
                'id' => $this->route('id'),
            ]);
        }
        
        // Ensure amount is a clean float if present
        if ($this->has('amount')) {
            $this->merge([
                'amount' => filter_var($this->amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            ]);
        }
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'transaction_date.before_or_equal' => 'You cannot record a cash advance for a future date.',
            'amount.min' => 'The cash advance amount must be at least 0.',
            'shift.in' => 'Please select a valid shift (Day or Night).',
        ];
    }
}
