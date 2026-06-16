<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\SecureFormRequest;
use App\Support\Security\PhoneNumberService;
use Closure;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends SecureFormRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $phoneNumbers = app(PhoneNumberService::class);

        return [
            'first_name' => ['nullable', 'string', 'max:120', 'regex:/^[\pL\s\'-]+$/u'],
            'last_name' => ['nullable', 'string', 'max:120', 'regex:/^[\pL\s\'-]+$/u'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                static function (string $attribute, mixed $value, Closure $fail) use ($phoneNumbers): void {
                    $phone = trim((string) $value);
                    if ($phone === '') {
                        return;
                    }

                    if (!$phoneNumbers->isValidInternational($phone)) {
                        $fail('Le numéro de téléphone est invalide. Utilisez un format international, par exemple +33612345678.');
                    }
                },
            ],
            'position' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=120,min_height=120,max_width=4000,max_height=4000'],
            'current_password' => ['nullable', 'string', 'required_with:new_password'],
            'new_password' => [
                'nullable',
                'string',
                'min:10',
                'max:128',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'Le prénom contient des caractères non autorisés.',
            'last_name.regex' => 'Le nom contient des caractères non autorisés.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'avatar.image' => 'Le fichier avatar doit être une image.',
            'avatar.mimes' => 'Formats autorisés pour l\'avatar: jpg, jpeg, png, webp.',
            'avatar.max' => 'L\'avatar ne doit pas dépasser 2 Mo.',
            'avatar.dimensions' => 'Dimensions avatar invalides (min 120x120, max 4000x4000).',
            'current_password.required_with' => 'Le mot de passe actuel est obligatoire pour changer le mot de passe.',
            'new_password.regex' => 'Le nouveau mot de passe doit contenir une minuscule, une majuscule, un chiffre et un caractère spécial.',
        ];
    }
}
