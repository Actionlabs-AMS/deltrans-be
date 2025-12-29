<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StackRunRequest extends FormRequest
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
            'reference_number' => 'nullable|string|max:255',
            'shipping_line_id' => 'required|integer|exists:shipping_lines,id',
            'cypa_id_from' => 'required|integer|exists:cypa_details,id',
            'cypa_id_to' => 'required|integer|exists:cypa_details,id',
            'quantity_of_container' => 'required|integer|min:1',
            'container_size' => [
                'required',
                'string',
                Rule::in(['20ft', '40ft']),
            ],
        ];

        // For update, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['cypa_id_from'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['cypa_id_to'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['quantity_of_container'] = 'sometimes|required|integer|min:1';
            $rules['container_size'] = [
                'sometimes',
                'required',
                'string',
                Rule::in(['20ft', '40ft']),
            ];
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
            'shipping_line_id.required' => 'The shipping line ID is required.',
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'cypa_id_from.required' => 'The CYPA ID (from) is required.',
            'cypa_id_from.exists' => 'The selected CYPA (from) does not exist.',
            'cypa_id_to.required' => 'The CYPA ID (to) is required.',
            'cypa_id_to.exists' => 'The selected CYPA (to) does not exist.',
            'quantity_of_container.required' => 'The quantity of container is required.',
            'quantity_of_container.min' => 'The quantity of container must be at least 1.',
            'container_size.required' => 'The container size is required.',
            'container_size.in' => 'The container size must be either 20ft or 40ft.',
        ];
    }
}


