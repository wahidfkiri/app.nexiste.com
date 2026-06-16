<?php

namespace NexusExtensions\GoogleMeet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleMeetMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:8000'],
            'location' => ['nullable', 'string', 'max:500'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'attendees' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['nullable', 'in:default,public,private,confidential'],
            'send_updates' => ['nullable', 'in:all,externalOnly,none'],
            'create_meet_link' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.required' => __('google-meet::messages.validation.summary_required'),
            'summary.min' => __('google-meet::messages.validation.summary_min'),
            'start_at.required' => __('google-meet::messages.validation.start_required'),
            'end_at.required' => __('google-meet::messages.validation.end_required'),
            'end_at.after' => __('google-meet::messages.validation.end_after'),
            'send_updates.in' => __('google-meet::messages.validation.send_updates_in'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $raw = trim((string) $this->input('attendees', ''));
            if ($raw === '') {
                return;
            }

            $emails = preg_split('/[,;\n]+/', $raw) ?: [];
            $invalid = [];

            foreach ($emails as $email) {
                $candidate = trim($email);
                if ($candidate === '') {
                    continue;
                }

                if (preg_match('/<([^>]+)>/', $candidate, $matches) === 1) {
                    $candidate = trim((string) $matches[1]);
                }

                $candidate = trim($candidate, " \t\n\r\0\x0B\"'");

                if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    $invalid[] = $candidate;
                }
            }

            if (!empty($invalid)) {
                $validator->errors()->add(
                    'attendees',
                    __('google-meet::messages.validation.attendees_invalid')
                );
            }
        });
    }
}
