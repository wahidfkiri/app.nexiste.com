<?php

namespace NexusExtensions\GoogleMeet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleMeetSelectCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'calendar_id.required' => __('google-meet::messages.validation.calendar_required'),
        ];
    }
}
