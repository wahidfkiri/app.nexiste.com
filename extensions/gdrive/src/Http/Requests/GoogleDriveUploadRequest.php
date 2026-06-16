<?php

namespace NexusExtensions\GoogleDrive\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDriveUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'],
            'parent_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => __('google-drive::messages.validation.file_required'),
            'file.file' => __('google-drive::messages.validation.file_invalid'),
            'file.max' => __('google-drive::messages.validation.file_max'),
            'parent_id.string' => __('google-drive::messages.validation.parent_id_string'),
            'parent_id.max' => __('google-drive::messages.validation.parent_id_max'),
        ];
    }
}
