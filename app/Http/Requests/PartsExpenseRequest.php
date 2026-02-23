<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartsExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            'plate_number' => 'nullable|string|max:50|exists:fleet_trucks,plate_number',
            'receipt_no' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:0',
            'article' => 'nullable|string|max:255',
            'amount_per_item' => 'nullable|numeric|min:0|max:999999999.99',
            'transaction_date' => 'required|date',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['transaction_date'] = 'sometimes|required|date';
        }

        return $rules;
    }
}
