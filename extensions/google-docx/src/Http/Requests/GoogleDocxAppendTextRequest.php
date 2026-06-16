<?php

namespace NexusExtensions\GoogleDocx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDocxAppendTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:50000'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('google-docx::messages.validation.text_required'),
            'text.string' => __('google-docx::messages.validation.text_string'),
            'text.max' => __('google-docx::messages.validation.text_max'),
        ];
    }
}
