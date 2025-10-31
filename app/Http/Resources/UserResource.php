<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
      'user_email' => $this->user_email,
      'first_name' => $this->user_details['first_name'] ?? null,
      'last_name' => $this->user_details['last_name'] ?? null,
      'nickname' => $this->user_details['nickname'] ?? null,
      'mobile_number' => $this->user_details['mobile_number'] ?? null,
      'contact_number' => $this->user_details['contact_number'] ?? null,
      'biography' => $this->user_details['biography'] ?? null,
      'attachment_file' => $this->user_details['attachment_file'] ?? null,
      'attachment_metadata' => $this->user_details['attachment_metadata'] ?? null,
      'user_role' => json_encode($this->user_role) ?? null,
      'user_role_name' => ($this->user_role) ? $this->user_role->name : 'Unassigned',
      'role_name' => ($this->user_role) ? $this->user_role->name : 'Unassigned', // Added for frontend table
      'role_id' => $this->role_id, // Added for frontend
      'theme' => $this->user_details['theme'] ?? null,
      'user_status' => $this->user_status, // Return numeric status for badge logic
      'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:m:s') : null
    ];
  }
}
