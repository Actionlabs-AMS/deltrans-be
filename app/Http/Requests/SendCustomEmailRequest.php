<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class SendCustomEmailRequest extends FormRequest
{
    private const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif,webp,zip';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cc = $this->input('cc');
        if (is_string($cc) && $cc !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $cc))));
            $this->merge(['cc' => $parts]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'cc' => 'nullable|array',
            'cc.*' => 'email|max:255',
            'attachment' => 'nullable|file|mimes:' . self::ALLOWED_MIMES . '|max:5120',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:' . self::ALLOWED_MIMES . '|max:5120',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $count = 0;

            $single = $this->file('attachment');
            if ($single instanceof UploadedFile && $single->isValid()) {
                $count++;
            }

            $multiple = $this->file('attachments', []);
            if ($multiple instanceof UploadedFile) {
                $multiple = [$multiple];
            }
            if (is_array($multiple)) {
                foreach ($multiple as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $count++;
                    }
                }
            }

            if ($count > 5) {
                $validator->errors()->add('attachments', 'You may attach at most 5 files in total.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Recipient email is required.',
            'email.email' => 'Recipient email must be a valid email address.',
            'subject.required' => 'Email subject is required.',
            'body.required' => 'Email body is required.',
            'attachment.file' => 'Attachment must be a valid file.',
            'attachment.mimes' => 'Attachment type is not allowed.',
            'attachment.max' => 'Attachment must not exceed 5MB.',
            'attachments.max' => 'You may attach at most 5 files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'One or more attachment types are not allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 5MB.',
        ];
    }
}
