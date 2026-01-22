<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateSoaRequest extends FormRequest
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
            if ($this->has('booking_id')) {
                $bookingId = $this->input('booking_id');
                $waybillCount = \App\Models\WaybillDetail::where('booking_id', $bookingId)->count();
                
                if ($waybillCount === 0) {
                    $validator->errors()->add('booking_id', 'The selected booking must have at least one waybill.');
                }
            }
        });
    }
}






