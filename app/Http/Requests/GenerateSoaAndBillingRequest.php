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
                'required_without:booking_ids',
                'nullable',
                'integer',
                'exists:bookings,id',
            ],
            'booking_ids' => [
                'required_without:booking_id',
                'nullable',
                'array',
                'min:1',
            ],
            'booking_ids.*' => [
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
            'booking_id.required_without' => 'Either booking_id or booking_ids is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'booking_ids.required_without' => 'Either booking_id or booking_ids is required.',
            'booking_ids.*.exists' => 'One or more selected bookings do not exist.',
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
            $ids = $this->resolveBookingIds();
            foreach ($ids as $bid) {
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $bid)->count();
                if ($waybillCount === 0) {
                    $validator->errors()->add('booking_ids', 'The selected booking (ID: ' . $bid . ') must have at least one waybill.');
                    return;
                }
            }
            if ($this->filled('shipping_line_id')) {
                foreach ($ids as $bid) {
                    $booking = \App\Models\Booking::find($bid);
                    if ($booking && $booking->shipping_line_id != $this->input('shipping_line_id')) {
                        $validator->errors()->add('booking_ids', 'The selected booking (ID: ' . $bid . ') does not belong to the selected shipping line.');
                        return;
                    }
                }
            }
        });
    }

    /**
     * Resolve booking_ids for the request (after validation).
     */
    public function resolveBookingIds(): array
    {
        if ($this->filled('booking_ids') && is_array($this->input('booking_ids'))) {
            return array_values(array_map('intval', $this->input('booking_ids')));
        }
        if ($this->filled('booking_id')) {
            return [(int) $this->input('booking_id')];
        }
        return [];
    }
}
