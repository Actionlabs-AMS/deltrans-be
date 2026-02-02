<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingRequest extends FormRequest
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
        $rules = [
            'reference_number' => 'nullable|string|max:255|unique:bookings,reference_number',
            'vessel' => 'nullable|string|max:255',
            'shipping_line_id' => 'required|integer|exists:shipping_lines,id',
            'cypa_id_from' => 'required|integer|exists:cypa_details,id',
            'cypa_id_to' => 'required|integer|exists:cypa_details,id',
            'expected_date' => 'nullable|date',
            'is_complete' => 'nullable|boolean',
        ];

        // For update, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['reference_number'] = 'sometimes|nullable|string|max:255|unique:bookings,reference_number,' . $this->route('id');
            $rules['vessel'] = 'sometimes|nullable|string|max:255';
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['cypa_id_from'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['cypa_id_to'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['expected_date'] = 'sometimes|nullable|date';
            $rules['is_complete'] = 'sometimes|nullable|boolean';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference_number.max' => 'The reference number must not exceed 255 characters.',
            'reference_number.unique' => 'The reference number has already been taken.',
            'shipping_line_id.required' => 'The shipping line ID is required.',
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'cypa_id_from.required' => 'The CYPA ID (from) is required.',
            'cypa_id_from.exists' => 'The selected CYPA (from) does not exist.',
            'cypa_id_to.required' => 'The CYPA ID (to) is required.',
            'cypa_id_to.exists' => 'The selected CYPA (to) does not exist.',
            'expected_date.date' => 'The expected date must be a valid date.',
            'vessel.max' => 'The vessel must not exceed 255 characters.',
            'is_complete.boolean' => 'The is_complete field must be true or false.',
        ];
    }
}
