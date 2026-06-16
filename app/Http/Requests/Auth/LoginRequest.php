<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\SecureFormRequest;

class LoginRequest extends SecureFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}
