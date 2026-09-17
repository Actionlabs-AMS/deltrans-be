<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FetchSoasByIdsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize ids / soa_ids aliases into statement_of_account_ids.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('statement_of_account_ids')) {
            return;
        }

        if ($this->has('ids')) {
            $this->merge(['statement_of_account_ids' => $this->input('ids')]);
            return;
        }

        if ($this->has('soa_ids')) {
            $this->merge(['statement_of_account_ids' => $this->input('soa_ids')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statement_of_account_ids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'statement_of_account_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('statement_of_accounts', 'id')->whereNull('deleted_at'),
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
            'statement_of_account_ids.required' => 'At least one statement of account is required.',
            'statement_of_account_ids.min' => 'At least one statement of account is required.',
            'statement_of_account_ids.max' => 'You can fetch at most 100 statements of account at a time.',
            'statement_of_account_ids.*.integer' => 'Each statement of account ID must be an integer.',
            'statement_of_account_ids.*.distinct' => 'Duplicate statement of account IDs are not allowed.',
            'statement_of_account_ids.*.exists' => 'One or more selected statements of account do not exist.',
        ];
    }

    /**
     * Unique integer SOA IDs in request order.
     *
     * @return array<int, int>
     */
    public function soaIds(): array
    {
        return array_values(array_unique(array_map(
            'intval',
            $this->input('statement_of_account_ids', [])
        )));
    }
}
