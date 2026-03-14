<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingStatementRequest extends FormRequest
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
     * All fields optional for partial update.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statement_of_account_id' => [
                'sometimes',
                'integer',
                'exists:statement_of_accounts,id',
            ],
            'billing_statement_no' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'prepared_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'payment_term' => [
                'nullable',
                'string',
                'max:255',
            ],
            'ci_date' => [
                'nullable',
                'date',
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'bus_style' => [
                'nullable',
                'string',
                'max:255',
            ],
            'has_details' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement_of_account_id.exists' => 'The selected statement of account does not exist.',
            'billing_statement_no.max' => 'The billing statement number must not exceed 255 characters.',
            'prepared_by.exists' => 'The selected user does not exist.',
            'payment_term.max' => 'The payment term must not exceed 255 characters.',
            'ci_date.date' => 'The CI date must be a valid date.',
            'due_date.date' => 'The due date must be a valid date.',
            'bus_style.max' => 'The business style must not exceed 255 characters.',
            'has_details.boolean' => 'The has details field must be true or false.',
        ];
    }
}
