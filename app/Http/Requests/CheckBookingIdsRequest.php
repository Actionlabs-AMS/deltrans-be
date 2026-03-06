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
                'required',
                'array',
                'min:1',
            ],
            'booking_ids.*' => [
                'integer',
                'exists:bookings,id',
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
            'booking_ids.required' => 'At least one booking is required.',
            'booking_ids.min' => 'At least one booking is required.',
            'booking_ids.*.exists' => 'One or more selected bookings do not exist.',
            'type.required' => 'Type is required.',
            'type.in' => 'Type must be 1 (SOA), 2 (Billing), or 3 (Invoice).',
        ];
    }
}
