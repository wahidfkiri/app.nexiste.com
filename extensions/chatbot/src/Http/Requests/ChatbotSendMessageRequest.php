<?php

namespace NexusExtensions\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotSendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxText = (int) config('chatbot.messages.max_text_length', 10000);
        $maxFileSize = (int) config('chatbot.messages.max_file_size_kb', 10240);
        $mimes = (array) config('chatbot.messages.allowed_mimes', []);
        $extensions = (array) config('chatbot.messages.allowed_extensions', []);
        $maxFiles = 6;

        $fileRules = [
            'file',
            'max:' . $maxFileSize,
        ];

        if (!empty($mimes)) {
            $fileRules[] = 'mimetypes:' . implode(',', $mimes);
        }

        if (!empty($extensions)) {
            $fileRules[] = 'mimes:' . implode(',', $extensions);
        }

        return [
            'room_id' => ['required', 'integer', 'exists:chatbot_rooms,id'],
            'text' => ['nullable', 'string', 'max:' . $maxText],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:chatbot_messages,id'],
            'files' => ['nullable', 'array', 'max:' . $maxFiles],
            'files.*' => $fileRules,
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $text = trim((string) $this->input('text', ''));
            $files = $this->file('files', []);

            if ($text === '' && empty($files)) {
                $validator->errors()->add('text', __('chatbot::messages.validation.message_empty_with_file_hint'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'room_id.required' => __('chatbot::messages.validation.room_required'),
            'room_id.exists' => __('chatbot::messages.validation.room_exists'),
            'text.max' => __('chatbot::messages.validation.text_max'),
            'files.*.file' => __('chatbot::messages.validation.file_invalid'),
            'files.max' => __('chatbot::messages.validation.files_max'),
            'files.*.max' => __('chatbot::messages.validation.file_size_max'),
            'files.*.mimetypes' => __('chatbot::messages.validation.file_mime'),
            'files.*.mimes' => __('chatbot::messages.validation.file_extension'),
        ];
    }
}
