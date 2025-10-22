<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
			'user_id' => $this->user_id,
			'file_name' => $this->file_name,
			'file_type' => $this->file_type,
			'file_size' => $this->file_size,
			'width' => $this->width,
			'height' => $this->height,
			'file_dimensions' => $this->file_dimensions,
			'file_url' => $this->file_url,
			'thumbnail_url' => $this->thumbnail_url,
			'caption' => $this->caption,
			'short_descriptions' => $this->short_descriptions,
			'created_at' => $this->updated_at->format('M d, Y'),
			'updated_at' => $this->updated_at->format('Y-m-d H:m:s')
		];
	}
}
