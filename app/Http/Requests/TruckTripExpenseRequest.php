<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TruckTripExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            'plate_number' => 'nullable|string|max:255',
            'helper_id' => 'nullable|integer|exists:helpers,id',
            'cash_on_hand' => 'nullable|numeric|min:0|max:999999999.99',
            'issued_cash_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'transaction_date' => 'required|date',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['transaction_date'] = 'sometimes|required|date';
            $rules['plate_number'] = 'sometimes|nullable|string|max:255';
        }

        return $rules;
    }
}
