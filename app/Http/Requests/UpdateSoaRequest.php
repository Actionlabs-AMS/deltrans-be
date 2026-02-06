<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSoaRequest extends FormRequest
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
            'shipping_line_id' => [
                'sometimes',
                'integer',
                'exists:shipping_lines,id',
            ],
            'dli_sa_number' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'booking_id' => [
                'sometimes',
                'integer',
                'exists:bookings,id',
            ],
            'work_order' => [
                'nullable',
                'string',
                'max:255',
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
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'work_order.max' => 'The work order must not exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('booking_id')) {
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $this->input('booking_id'))->count();
                if ($waybillCount === 0) {
                    $validator->errors()->add('booking_id', 'The selected booking must have at least one waybill.');
                }
            }
            if ($this->filled('booking_id') && $this->filled('shipping_line_id')) {
                $booking = \App\Models\Booking::find($this->input('booking_id'));
                if ($booking && $booking->shipping_line_id != $this->input('shipping_line_id')) {
                    $validator->errors()->add('booking_id', 'The selected booking does not belong to the selected shipping line.');
                }
            }
        });
    }
}
