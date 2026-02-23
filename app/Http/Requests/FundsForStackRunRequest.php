<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FundsForStackRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', '1st', '2nd'])],
            'remarks' => 'nullable|string|max:65535',
            'amount' => 'required|numeric|min:0|max:999999999.99',
            'transaction_date' => 'required|date',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['amount'] = 'sometimes|required|numeric|min:0|max:999999999.99';
            $rules['transaction_date'] = 'sometimes|required|date';
        }

        return $rules;
    }
}
