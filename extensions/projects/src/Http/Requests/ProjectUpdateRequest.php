<?php

namespace NexusExtensions\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:planning,active,on_hold,completed,archived'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'sync_google_calendar' => ['nullable', 'boolean'],
            'calendar_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
