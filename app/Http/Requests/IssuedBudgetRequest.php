<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssuedBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0|max:999999999.99',
            'source' => 'nullable|string|max:255',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['transaction_date'] = 'sometimes|required|date';
            $rules['amount'] = 'sometimes|required|numeric|min:0|max:999999999.99';
        }

        return $rules;
    }
}
