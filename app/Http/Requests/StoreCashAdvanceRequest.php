<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * type: 1 = driver (driver_cash_advancement_history), 2 = helper (helper_cash_advancement_history).
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'integer', Rule::in([1, 2])],
            'driver_id' => ['required_if:type,1', 'nullable', 'integer', 'exists:drivers,id'],
            'helper_id' => ['required_if:type,2', 'nullable', 'integer', 'exists:helpers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type must be 1 (driver) or 2 (helper).',
            'driver_id.required_if' => 'Driver ID is required when type is 1 (driver).',
            'helper_id.required_if' => 'Helper ID is required when type is 2 (helper).',
        ];
    }
}
