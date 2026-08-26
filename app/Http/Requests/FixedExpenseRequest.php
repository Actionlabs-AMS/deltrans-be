<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FixedExpenseRequest extends FormRequest
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
            'shipping_line_id' => 'required|integer|exists:shipping_lines,id',
            'cypa_id_from' => 'required|integer|exists:cypa_details,id',
            'cypa_id_to' => 'required|integer|exists:cypa_details,id',
            'container_size' => [
                'required',
                'string',
                Rule::in(['20ft', '40ft', '45ft']),
            ],
            'docs_fee' => 'nullable|numeric|min:0',
            'online_booking_fee' => 'nullable|numeric|min:0',
            'stack_run' => 'nullable|numeric|min:0',
            'expenses' => 'nullable|numeric|min:0',
        ];

        // For update, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['cypa_id_from'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['cypa_id_to'] = 'sometimes|required|integer|exists:cypa_details,id';
            $rules['container_size'] = [
                'sometimes',
                'required',
                'string',
                Rule::in(['20ft', '40ft', '45ft']),
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
            'shipping_line_id.required' => 'The shipping line ID is required.',
            'shipping_line_id.exists' => 'The selected shipping line does not exist.',
            'cypa_id_from.required' => 'The CYPA ID (from) is required.',
            'cypa_id_from.exists' => 'The selected CYPA (from) does not exist.',
            'cypa_id_to.required' => 'The CYPA ID (to) is required.',
            'cypa_id_to.exists' => 'The selected CYPA (to) does not exist.',
            'container_size.required' => 'The container size is required.',
            'container_size.in' => 'The container size must be either 20ft, 40ft, or 45ft.',
            'docs_fee.numeric' => 'The docs fee must be a valid number.',
            'docs_fee.min' => 'The docs fee must be at least 0.',
            'stack_run.numeric' => 'The stack run must be a valid number.',
            'stack_run.min' => 'The stack run amount must be at least 0.',
            'expenses.numeric' => 'The expenses must be a valid number.',
            'expenses.min' => 'The expenses amount must be at least 0.',
            'online_booking_fee.numeric' => 'The online booking fee must be a valid number.',
            'online_booking_fee.min' => 'The online booking fee must be at least 0.',
        ];
    }
}

