<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoaDataOptionRequest extends FormRequest
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
            'parent_id' => [
                'nullable',
                'integer',
                'exists:soa_data_options,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->id && $value == $this->id) {
                        $fail('An option cannot be its own parent.');
                    }
                }
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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
            'name.required' => 'The name is required.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'parent_id.exists' => 'The selected parent option does not exist.',
            'parent_id.integer' => 'The parent option must be a valid option.',
            'parent_id.*' => 'An option cannot be its own parent.',
        ];
    }
}

