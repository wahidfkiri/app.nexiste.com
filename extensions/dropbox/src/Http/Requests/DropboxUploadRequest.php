<?php

namespace NexusExtensions\Dropbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropboxUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:102400'],
            'parent_id' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => __('dropbox::messages.validation.files_required'),
            'files.array' => __('dropbox::messages.validation.files_array'),
            'files.min' => __('dropbox::messages.validation.files_required'),
            'files.*.required' => __('dropbox::messages.validation.file_required'),
            'files.*.file' => __('dropbox::messages.validation.file_invalid'),
            'files.*.max' => __('dropbox::messages.validation.file_max'),
            'parent_id.string' => __('dropbox::messages.validation.parent_id_string'),
            'parent_id.max' => __('dropbox::messages.validation.parent_id_max'),
        ];
    }
}
