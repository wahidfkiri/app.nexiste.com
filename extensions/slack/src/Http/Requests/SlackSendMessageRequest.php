<?php

namespace NexusExtensions\Slack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlackSendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:40000'],
            'thread_ts' => ['nullable', 'string', 'max:40'],
        ];
    }
}

