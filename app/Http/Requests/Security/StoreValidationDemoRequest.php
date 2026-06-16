<?php

namespace App\Http\Requests\Security;

use App\Http\Requests\SecureFormRequest;
use Illuminate\Validation\Rule;

class StoreValidationDemoRequest extends SecureFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\s\'-]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:10', 'max:128', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/'],
            'age' => ['nullable', 'integer', 'between:18,100'],
            'budget' => ['nullable', 'numeric', 'between:0,999999999.99'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s().-]{8,20}$/'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'role' => ['required', Rule::in(['owner', 'admin', 'manager', 'user'])],
            'contact_channel' => ['required', Rule::in(['email', 'phone'])],
            'interests' => ['nullable', 'array'],
            'interests.*' => [Rule::in(['crm', 'facturation', 'stock', 'projets', 'support'])],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=120,min_height=120,max_width=4000,max_height=4000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv', 'max:5120'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Le nom complet est obligatoire.',
            'full_name.regex' => 'Le nom complet contient des caractères non autorisés.',
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'Le format de l’email est invalide.',
            'password.regex' => 'Le mot de passe doit contenir une minuscule, une majuscule, un chiffre et un caractère spécial.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'age.integer' => 'L’âge doit être un nombre entier.',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'phone.required' => 'Le téléphone est obligatoire.',
            'phone.regex' => 'Le format du téléphone est invalide.',
            'website.url' => 'Le site web doit être une URL valide (https://...).',
            'birth_date.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
            'role.in' => 'Le rôle sélectionné est invalide.',
            'contact_channel.in' => 'Le canal de contact est invalide.',
            'interests.*.in' => 'Une option sélectionnée dans centres d’intérêt est invalide.',
            'avatar.image' => 'L’avatar doit être une image valide.',
            'avatar.mimes' => 'Formats avatar autorisés: jpg, jpeg, png, webp.',
            'avatar.max' => 'L’avatar ne doit pas dépasser 2 Mo.',
            'attachment.mimes' => 'Le document doit être un PDF, DOC, DOCX, XLS, XLSX ou CSV.',
            'attachment.max' => 'Le document ne doit pas dépasser 5 Mo.',
            'terms.accepted' => 'Vous devez accepter les conditions.',
        ];
    }
}
