<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HelperRequest extends FormRequest
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
      "first_name" => "required|string|max:191|regex:/^[a-zA-Z\s]+$/",
      "last_name" => "required|string|max:191|regex:/^[a-zA-Z\s]+$/",
      "contact_number" => "required|string|max:191|unique:helpers,contact_number,".$this->id,
      "active_status" => "nullable|boolean",
    ];
  }

  public function messages(): array
  {
    return [
      "first_name.required" => "The first name field is required.",
      "first_name.regex" => "The first name field must only contain letters and spaces.",
      "last_name.required" => "The last name field is required.",
      "last_name.regex" => "The last name field must only contain letters and spaces.",
      "contact_number.required" => "The contact number field is required.",
      "contact_number.unique" => "This contact number is already registered.",
      "active_status.boolean" => "The active status must be true or false.",
    ];
  }
}








