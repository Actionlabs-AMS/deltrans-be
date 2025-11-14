<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingLineRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:shipping_lines,name,' . $this->id,
            'email_address' => 'required|email|max:255|unique:shipping_lines,email_address,' . $this->id,
            'address' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:20',
            'landlines' => 'nullable|array',
            'landlines.*' => 'nullable|string|max:20',
            'shipping_lines_template' => 'nullable|array',
            'transaction_information_template' => 'nullable|array',
            'fax_no' => 'nullable|string|max:20',
            'tin' => 'nullable|string|max:50',
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
            'name.required' => 'The shipping line name is required.',
            'name.unique' => 'This shipping line name is already taken.',
            'email_address.required' => 'The email address is required.',
            'email_address.email' => 'Please provide a valid email address.',
            'email_address.unique' => 'This email address is already registered.',
            'landlines.array' => 'Landlines must be an array.',
            'landlines.*.string' => 'Each landline must be a string.',
        ];
    }
}

