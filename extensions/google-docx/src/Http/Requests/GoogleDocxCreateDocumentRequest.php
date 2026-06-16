<?php

namespace NexusExtensions\GoogleDocx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDocxCreateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:50000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('google-docx::messages.validation.title_required'),
            'title.string' => __('google-docx::messages.validation.title_string'),
            'title.max' => __('google-docx::messages.validation.title_max'),
            'content.string' => __('google-docx::messages.validation.content_string'),
            'content.max' => __('google-docx::messages.validation.content_max'),
        ];
    }
}
