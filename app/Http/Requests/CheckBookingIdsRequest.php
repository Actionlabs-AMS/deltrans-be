<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckBookingIdsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking_ids' => [
                'nullable',
                'array',
                'min:1',
            ],
            'booking_ids.*' => [
                'integer',
                'exists:bookings,id',
            ],
            'statement_of_account_id' => [
                'nullable',
                'integer',
                'exists:statement_of_accounts,id',
            ],
            'statement_of_account_ids' => [
                'nullable',
                'array',
                'min:1',
            ],
            'statement_of_account_ids.*' => [
                'integer',
                'exists:statement_of_accounts,id',
            ],
            'type' => [
                'required',
                'integer',
                'in:1,2,3',
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
            'booking_ids.min' => 'At least one booking is required.',
            'booking_ids.*.exists' => 'One or more selected bookings do not exist.',
            'statement_of_account_id.exists' => 'The selected statement of account does not exist.',
            'statement_of_account_ids.min' => 'At least one statement of account is required.',
            'statement_of_account_ids.*.exists' => 'One or more selected statements of account do not exist.',
            'type.required' => 'Type is required.',
            'type.in' => 'Type must be 1 (SOA), 2 (Billing), or 3 (Invoice).',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bookingIds = $this->input('booking_ids');
            $hasBookingIds = is_array($bookingIds) && count($bookingIds) > 0;
            $hasSoaId = !is_null($this->input('statement_of_account_id')) && $this->input('statement_of_account_id') !== '';
            $soaIds = $this->input('statement_of_account_ids');
            $hasSoaIds = is_array($soaIds) && count($soaIds) > 0;

            if (!$hasBookingIds && !$hasSoaId && !$hasSoaIds) {
                $validator->errors()->add(
                    'booking_ids',
                    'Provide either booking_ids, statement_of_account_id, or statement_of_account_ids.'
                );
            }
        });
    }
}
