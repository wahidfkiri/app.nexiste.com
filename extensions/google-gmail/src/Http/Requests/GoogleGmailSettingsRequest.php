<?php

namespace NexusExtensions\GoogleGmail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleGmailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_enabled' => ['nullable', 'boolean'],
            'signature_html' => ['nullable', 'string', 'max:200000'],
            'signature_on_replies' => ['nullable', 'boolean'],
            'signature_on_forwards' => ['nullable', 'boolean'],
            'default_cc' => ['nullable', 'string', 'max:2000'],
            'default_bcc' => ['nullable', 'string', 'max:2000'],
            'polling_interval_seconds' => ['nullable', 'integer', 'min:15', 'max:300'],
            'main_labels' => ['nullable', 'array', 'max:10'],
            'main_labels.*' => ['string', 'max:120'],
        ];
    }
}