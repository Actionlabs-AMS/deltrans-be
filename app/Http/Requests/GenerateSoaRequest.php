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
            'soa_coverage_from' => [
                'required',
                'date',
            ],
            'soa_coverage_to' => [
                'required',
                'date',
                'after_or_equal:soa_coverage_from',
            ],
            'waybill_id' => [
                'nullable',
                'array',
            ],
            'waybill_id.*' => [
                'integer',
            ],
            'signature' => [
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
            'soa_coverage_from.required' => 'The coverage start date is required.',
            'soa_coverage_from.date' => 'The coverage start date must be a valid date.',
            'soa_coverage_to.required' => 'The coverage end date is required.',
            'soa_coverage_to.date' => 'The coverage end date must be a valid date.',
            'soa_coverage_to.after_or_equal' => 'The coverage end date must be after or equal to the start date.',
            'waybill_id.array' => 'The waybill IDs must be an array.',
            'signature.boolean' => 'The signature field must be true or false.',
        ];
    }
}






