<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RatePerClientRequest extends FormRequest
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
            'no_of_days' => 'required|integer|min:1',
            'requirements' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'cypa_id' => 'required|integer|min:0',
            'stack_run' => 'required|numeric|min:0',
            'container_size' => [
                'required',
                'string',
                Rule::in(['20ft', '40ft', '20ft(offhire)', '40ft(offhire)']),
            ],
            'rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|integer|in:0,1',
        ];

        // For update, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['shipping_line_id'] = 'sometimes|required|integer|exists:shipping_lines,id';
            $rules['no_of_days'] = 'sometimes|required|integer|min:1';
            $rules['cypa_id'] = 'sometimes|required|integer|min:0';
            $rules['stack_run'] = 'sometimes|required|numeric|min:0';
            $rules['container_size'] = [
                'sometimes',
                'required',
                'string',
                Rule::in(['20ft', '40ft', '20ft(offhire)', '40ft(offhire)']),
            ];
            $rules['rate'] = 'sometimes|required|numeric|min:0';
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate cypa_id exists if it's greater than 0
            if ($this->has('cypa_id') && $this->cypa_id > 0) {
                if (!\App\Models\ContainerYard::where('id', $this->cypa_id)->exists()) {
                    $validator->errors()->add('cypa_id', 'The selected CYPA does not exist.');
                }
            }
        });
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
            'no_of_days.required' => 'The number of days is required.',
            'no_of_days.min' => 'The number of days must be at least 1.',
            'cypa_id.required' => 'The CYPA ID is required.',
            'cypa_id.min' => 'The CYPA ID must be 0 (all) or a valid CYPA ID.',
            'cypa_id.exists' => 'The selected CYPA does not exist.',
            'stack_run.required' => 'The stack run amount is required.',
            'stack_run.numeric' => 'The stack run must be a valid number.',
            'stack_run.min' => 'The stack run amount must be at least 0.',
            'container_size.required' => 'The container size is required.',
            'container_size.in' => 'The container size must be one of: 20ft, 40ft, 20ft(offhire), or 40ft(offhire).',
            'rate.required' => 'The rate is required.',
            'rate.numeric' => 'The rate must be a valid number.',
            'rate.min' => 'The rate must be at least 0.',
            'is_active.in' => 'The is_active field must be either 0 or 1.',
        ];
    }
}

