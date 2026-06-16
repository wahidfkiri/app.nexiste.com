<?php

namespace NexusExtensions\NotionWorkspace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class NotionPageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectRules = ['nullable', 'integer'];
        if (Schema::hasTable('projects')) {
            $projectRules[] = 'exists:projects,id';
        }

        return [
            'workspace_id' => ['required', 'integer', 'exists:notion_workspaces,id'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:notion_pages,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'project_id' => $projectRules,
            'icon' => ['nullable', 'string', 'max:50'],
            'cover_color' => ['nullable', 'string', 'max:20'],
            'visibility' => ['nullable', 'in:private,team,public'],
            'is_template' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }
}
