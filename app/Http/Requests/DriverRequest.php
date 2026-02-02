<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
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
    $driverId = $this->route('id') ?? $this->id;

    return [
      //"first_name" => "required|string|max:191|regex:/^[a-zA-Z\s]+$/",
      'first_name' => [
          'required',
          'string',
          'max:255',
          'regex:/^[a-zA-Z\s]+$/',
          Rule::unique('drivers')
              ->where(function ($query) {
                  return $query->where('last_name', $this->last_name)
                              ->whereNull('deleted_at');
              })
              ->ignore($driverId),
      ],
      "last_name" => "required|string|max:191|regex:/^[a-zA-Z\s]+$/",
      //"contact_number" => "required|string|max:191|unique:drivers,contact_number,".$this->id,
      "contact_number" => [
          "required",
          "string",
          "max:191",
          Rule::unique('drivers', 'contact_number')
              ->ignore($this->id) 
              ->whereNull('deleted_at')
      ],
      "is_active" => "nullable|integer|in:0,1",
      "assigned_truck_plate_numbers" => "nullable|array",
      "assigned_truck_plate_numbers.*" => "string|max:191",
      "stack_run" => "nullable|array",
      "stack_run.*" => "string|max:191",
      "helpers_id" => "nullable|array",
      "helpers_id.*" => "integer|exists:helpers,id",
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
      "is_active.integer" => "The is_active field must be an integer.",
      "is_active.in" => "The is_active field must be either 0 or 1.",
      "assigned_truck_plate_numbers.array" => "The assigned truck plate numbers must be an array.",
      "stack_run.array" => "The stack run must be an array.",
      "helpers_id.array" => "The helpers ID must be an array.",
      "helpers_id.*.exists" => "One or more helper IDs do not exist.",
    ];
  }
}

