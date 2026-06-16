<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StartGlobalDataExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && method_exists($user, 'canManageTenant') ? $user->canManageTenant() : false);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:google-drive,dropbox'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'Choisissez une destination de sauvegarde.',
            'provider.in' => 'La destination de sauvegarde doit être Google Drive ou Dropbox.',
        ];
    }
}
