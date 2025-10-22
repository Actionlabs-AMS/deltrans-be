<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
			"user_login" => "required|string|unique:users,user_login,".$this->id,
			"user_email" => "required|email|unique:users,user_email,".$this->id,
			"user_pass" => [
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
