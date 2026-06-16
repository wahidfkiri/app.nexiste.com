<?php

namespace NexusExtensions\GoogleSheets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleSheetsCreateSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:500'],
            'sheet_titles' => ['nullable', 'array', 'max:10'],
            'sheet_titles.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('google-sheets::messages.validation.title_required'),
            'title.string' => __('google-sheets::messages.validation.title_string'),
            'title.max' => __('google-sheets::messages.validation.title_max'),
            'sheet_titles.array' => __('google-sheets::messages.validation.sheet_titles_array'),
            'sheet_titles.max' => __('google-sheets::messages.validation.sheet_titles_max'),
            'sheet_titles.*.string' => __('google-sheets::messages.validation.sheet_title_string'),
            'sheet_titles.*.max' => __('google-sheets::messages.validation.sheet_title_max'),
        ];
    }
}
