<?php

namespace NexusExtensions\GoogleDocx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDocxReplaceTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'max:500'],
            'replace' => ['nullable', 'string', 'max:50000'],
            'match_case' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => __('google-docx::messages.validation.search_required'),
            'search.string' => __('google-docx::messages.validation.search_string'),
            'search.max' => __('google-docx::messages.validation.search_max'),
            'replace.string' => __('google-docx::messages.validation.replace_string'),
            'replace.max' => __('google-docx::messages.validation.replace_max'),
        ];
    }
}
