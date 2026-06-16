<?php

namespace NexusExtensions\Dropbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropboxFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('dropbox::messages.validation.folder_name_required'),
            'name.string' => __('dropbox::messages.validation.folder_name_string'),
            'name.max' => __('dropbox::messages.validation.folder_name_max'),
            'parent_id.string' => __('dropbox::messages.validation.parent_id_string'),
            'parent_id.max' => __('dropbox::messages.validation.parent_id_max'),
        ];
    }
}
