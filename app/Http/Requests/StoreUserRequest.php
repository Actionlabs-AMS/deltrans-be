<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
			"user_login" => "required|string|unique:users,user_login",
			"user_email" => "required|email|unique:users,user_email",
		'user_role' => [
				'required',
				function ($attribute, $value, $fail) {
						// Handle both object/array and JSON string
						$decoded = null;
						
						if (is_array($value) || is_object($value)) {
								// Already an object/array
								$decoded = is_array($value) ? $value : (array)$value;
						} else if (is_string($value)) {
								// Try to decode JSON string
								$decoded = json_decode($value, true);
								if (json_last_error() !== JSON_ERROR_NONE) {
										return $fail("The $attribute must be a valid JSON string or object.");
								}
						} else {
								return $fail("The $attribute must be a valid JSON object or string.");
						}

						// Check if decoding failed or result is not an array
						if (!is_array($decoded)) {
								return $fail("The $attribute must be a valid JSON object.");
						}

						// If it's an empty array, reject
						if (empty($decoded)) {
								return $fail("The $attribute cannot be an empty array.");
						}

						// Must contain 'id'
						if (!isset($decoded['id'])) {
								return $fail("The $attribute must contain an 'id'.");
						}

						// Optional: reject certain roles by ID
						if ($decoded['id'] == 1) {
								return $fail("The selected role is not allowed.");
						}
				}
		],
			"user_pass" => [
				'required',
				'string',
				'min:8',              // must be at least 8 characters in length
				'regex:/[a-z]/',      // must contain at least one lowercase letter
				'regex:/[A-Z]/',      // must contain at least one uppercase letter
				'regex:/[0-9]/',      // must contain at least one digit
				'regex:/[@$!%*#?&]/', // must contain a special character
			],
		];
	}

	public function messages(): array
	{
		return [
			"user_login.unique" => "The username has already been taken.",
			"user_email.email" => "The email field must be a valid email address.",
			"user_email.unique" => "The email has already been taken.",
			"user_pass.required" => "The password field is required."
		];
	}
}
