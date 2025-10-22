<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
      'id' => $this->id,
      'user_login' => $this->user_login,
      'first_name' => (isset($this->user_details['first_name'])) ? $this->user_details['first_name'] : '',
      'last_name' => (isset($this->user_details['last_name'])) ? $this->user_details['last_name'] : '',
      'attachment_file' => (isset($this->user_details['attachment_file'])) ? $this->user_details['attachment_file'] : '',
      'user_routes' => $this->user_role->getUserRoutes(),
      'theme' => (isset($this->user_details['theme'])) ? $this->user_details['theme'] : '',
      'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:m:s') : null
    ];
	}
}
