<?php

namespace NexusExtensions\GoogleSheets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleSheetsWriteRangeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'range'    => ['required', 'string', 'max:255'],
            'values'   => ['required', 'array'],
            'values.*' => ['array'],
        ];
    }

    public function messages(): array
    {
        return [
            'range.required' => __('google-sheets::messages.validation.range_required'),
            'range.string' => __('google-sheets::messages.validation.range_string'),
            'range.max' => __('google-sheets::messages.validation.range_max'),
            'values.required' => __('google-sheets::messages.validation.values_required'),
            'values.array' => __('google-sheets::messages.validation.values_array'),
            'values.*.array' => __('google-sheets::messages.validation.values_array'),
        ];
    }
}
