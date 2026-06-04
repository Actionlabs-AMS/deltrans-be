<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShiftBudgetBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shiftRule = Rule::in(['Day', 'Night', '1st', '2nd']);

        if (str_ends_with($this->path(), 'shift-balances/show')) {
            return [
                'transaction_date' => 'required|date',
                'shift' => ['required', 'string', $shiftRule],
            ];
        }

        if (str_ends_with($this->path(), 'shift-balances/recalculate')) {
            return [
                'transaction_date' => 'nullable|date|required_without:recalculate_all',
                'shift' => ['nullable', 'string', $shiftRule, 'required_with:transaction_date'],
                'recalculate_all' => 'nullable|boolean',
            ];
        }

        return [
            'transaction_date' => 'nullable|date',
            'transaction_date_from' => 'nullable|date',
            'transaction_date_to' => 'nullable|date',
            'shift' => ['nullable', 'string', $shiftRule],
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
