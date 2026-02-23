<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift' => ['nullable', 'string', Rule::in(['Day', 'Night', 'All'])],
            'transaction_date_from' => 'nullable|date',
            'transaction_date_to' => 'nullable|date',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('shift') && $this->shift === '') {
            $this->merge(['shift' => 'All']);
        }
    }
}
