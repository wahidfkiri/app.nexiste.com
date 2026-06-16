<?php

namespace NexusExtensions\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectTaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:7000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimate_hours' => ['nullable', 'numeric', 'min:0'],
            'spent_hours' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'sync_google_calendar' => ['nullable', 'boolean'],
            'calendar_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
