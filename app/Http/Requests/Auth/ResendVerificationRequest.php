<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\SecureFormRequest;

class ResendVerificationRequest extends SecureFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email' => 'Adresse email invalide.',
        ];
    }
}
