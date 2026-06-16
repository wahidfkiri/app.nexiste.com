<?php

namespace NexusExtensions\GoogleGmail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleGmailReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cc' => ['nullable', 'string', 'max:2000'],
            'bcc' => ['nullable', 'string', 'max:2000'],
            'body_text' => ['nullable', 'string', 'max:200000', 'required_without:body_html'],
            'body_html' => ['nullable', 'string', 'max:300000', 'required_without:body_text'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
