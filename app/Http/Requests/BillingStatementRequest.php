<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillingStatementRequest extends FormRequest
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
            'shipping_line_id' => [
                'required',
                'integer',
                'exists:shipping_lines,id',
            ],
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'billing_statement_no' => [
                'required',
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
            'is_paid' => [
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
            'shipping_line_id.required' => 'The shipping line is required.',
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'booking_id.required' => 'The booking is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'billing_statement_no.required' => 'The billing statement number is required.',
            'billing_statement_no.max' => 'The billing statement number must not exceed 255 characters.',
            'prepared_by.exists' => 'The selected user does not exist.',
            'payment_term.max' => 'The payment term must not exceed 255 characters.',
            'ci_date.date' => 'The CI date must be a valid date.',
            'due_date.date' => 'The due date must be a valid date.',
            'bus_style.max' => 'The business style must not exceed 255 characters.',
            'has_details.boolean' => 'The has details field must be true or false.',
            'is_paid.boolean' => 'The is paid field must be true or false.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('booking_id') && $this->has('shipping_line_id')) {
                $booking = \App\Models\Booking::find($this->input('booking_id'));
                
                if ($booking && $booking->shipping_line_id != $this->input('shipping_line_id')) {
                    $validator->errors()->add('booking_id', 'The selected booking does not belong to the selected shipping line.');
                }
            }
        });
    }
}
