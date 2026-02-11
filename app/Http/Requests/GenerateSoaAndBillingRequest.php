<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSoaAndBillingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for combined SOA + Billing Statement generation.
     * Combines required details from both into one request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // SOA fields
            'shipping_line_id' => [
                'required',
                'integer',
                'exists:shipping_lines,id',
            ],
            'dli_sa_number' => [
                'required',
                'string',
                'max:255',
            ],
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'work_order' => [
                'nullable',
                'string',
                'max:255',
            ],
            // Billing Statement fields
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
            'dli_sa_number.required' => 'The DLI SA number is required.',
            'booking_id.required' => 'The booking is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'billing_statement_no.required' => 'The billing statement number is required.',
            'billing_statement_no.max' => 'The billing statement number must not exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('booking_id')) {
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $this->input('booking_id'))->count();
                if ($waybillCount === 0) {
                    $validator->errors()->add('booking_id', 'The selected booking must have at least one waybill.');
                }
            }
            if ($this->has('booking_id') && $this->has('shipping_line_id')) {
                $booking = \App\Models\Booking::find($this->input('booking_id'));
                if ($booking && $booking->shipping_line_id != $this->input('shipping_line_id')) {
                    $validator->errors()->add('booking_id', 'The selected booking does not belong to the selected shipping line.');
                }
            }
        });
    }
}
