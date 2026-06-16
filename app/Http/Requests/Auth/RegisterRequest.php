<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\SecureFormRequest;

class RegisterRequest extends SecureFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\s\'-]+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\s\'-]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'company' => ['nullable', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.regex' => 'Le prénom contient des caractères non autorisés.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.regex' => 'Le nom contient des caractères non autorisés.',
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'Le format de l’email est invalide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.regex' => 'Le mot de passe doit contenir une minuscule, une majuscule, un chiffre et un caractère spécial.',
        ];
    }
}
