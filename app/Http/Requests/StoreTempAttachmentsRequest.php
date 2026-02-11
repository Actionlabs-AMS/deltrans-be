<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTempAttachmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize file(s) to array. Accept both "images" and "images[]" (PHP needs "images[]"
     * for multiple files to be aggregated). Replace the file bag with our array and clear
     * Laravel's convertedFiles cache so validation and controller both see an array.
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->files->get('images') ?? $this->files->get('images[]');
        if ($raw === null) {
            return;
        }
        $normalized = is_array($raw) ? array_values($raw) : [$raw];
        $this->files->remove('images');
        $this->files->remove('images[]');
        $this->files->set('images', $normalized);

        // Clear cache so next allFiles() / file() use the updated bag
        $ref = new \ReflectionProperty(\Illuminate\Http\Request::class, 'convertedFiles');
        $ref->setAccessible(true);
        $ref->setValue($this, null);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
            'images.required' => 'Please select at least one image to upload.',
            'images.array' => 'Images must be sent as an array.',
            'images.max' => 'You may upload at most 10 images.',
            'images.*.required' => 'Each image is required.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be jpeg, png, jpg, gif, or webp.',
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
    }
}
