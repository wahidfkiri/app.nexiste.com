<?php

namespace NexusExtensions\Slack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlackSelectChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'string', 'max:100'],
        ];
    }
}

