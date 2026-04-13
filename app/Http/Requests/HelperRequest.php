<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HelperRequest extends FormRequest
{
  /** Unicode letters (e.g. ñ, é), marks, spaces, hyphen, apostrophe — for person names */
  private const NAME_CHAR_REGEX = 'regex:/^[\p{L}\p{M}\s\-\'.]+$/u';

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

    $helperId = $this->route('id') ?? $this->id;

    return [
      //"first_name" => "required|string|max:191|regex:/^[a-zA-Z\s]+$/",
      'first_name' => [
          'required',
          'string',
          'max:255',
          self::NAME_CHAR_REGEX,
          Rule::unique('helpers')
              ->where(function ($query) {
                  return $query->where('last_name', $this->last_name)
                              ->whereNull('deleted_at');
              })
              ->ignore($helperId),
      ],
      'last_name' => 'required|string|max:191|' . self::NAME_CHAR_REGEX,
      //"contact_number" => "required|string|max:191|unique:helpers,contact_number,".$this->id,
      "contact_number" => [
          "required",
          "string",
          "max:191",
          Rule::unique('helpers', 'contact_number')
              ->ignore($this->id) 
              ->whereNull('deleted_at')
      ],
      "is_active" => "nullable|integer|in:0,1",
    ];
  }

  public function messages(): array
  {
    return [
      "first_name.required" => "The first name field is required.",
      'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
      'last_name.required' => 'The last name field is required.',
      'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
      "contact_number.required" => "The contact number field is required.",
      "contact_number.unique" => "This contact number is already registered.",
      "is_active.integer" => "The is_active field must be an integer.",
      "is_active.in" => "The is_active field must be either 0 or 1.",
    ];
  }
}









