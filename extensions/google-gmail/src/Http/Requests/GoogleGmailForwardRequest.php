<?php

namespace NexusExtensions\GoogleGmail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleGmailForwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:2000'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'bcc' => ['nullable', 'string', 'max:2000'],
            'body_text' => ['nullable', 'string', 'max:200000'],
            'body_html' => ['nullable', 'string', 'max:300000'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
