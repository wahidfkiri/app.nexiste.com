<?php

namespace NexusExtensions\GoogleDrive\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDriveFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('google-drive::messages.validation.folder_name_required'),
            'name.string' => __('google-drive::messages.validation.folder_name_string'),
            'name.max' => __('google-drive::messages.validation.folder_name_max'),
            'parent_id.string' => __('google-drive::messages.validation.parent_id_string'),
            'parent_id.max' => __('google-drive::messages.validation.parent_id_max'),
        ];
    }
}
