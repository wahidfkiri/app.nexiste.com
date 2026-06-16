<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\SecureFormRequest;
use App\Support\Security\PhoneNumberService;
use Closure;
use Illuminate\Validation\Rule;

class UpdateGlobalSettingsRequest extends SecureFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return (bool) ($user && method_exists($user, 'canManageTenant') && $user->canManageTenant());
    }

    public function rules(): array
    {
        $countries = collect((array) config('onboarding.countries', []))
            ->pluck('code')
            ->filter()
            ->map(fn ($code) => strtoupper((string) $code))
            ->unique()
            ->values()
            ->all();

        $currencies = array_keys((array) config('onboarding.currencies', []));
        if (empty($currencies)) {
            $currencies = ['EUR', 'USD', 'MAD', 'GBP', 'CAD', 'CHF'];
        }

        $phoneNumbers = app(PhoneNumberService::class);

        return [
            'tenant_name' => ['required', 'string', 'min:2', 'max:255'],
            'tenant_email' => ['nullable', 'email:rfc', 'max:255'],
            'tenant_phone' => [
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
            'tenant_address' => ['nullable', 'string', 'max:600'],
            'tenant_timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'tenant_locale' => ['required', 'string', Rule::in(['fr', 'en'])],
            'tenant_currency' => ['required', 'string', Rule::in($currencies)],

            'company_country' => ['nullable', 'string', Rule::in($countries)],
            'company_postal_code' => ['nullable', 'string', 'max:20'],
            'company_city' => ['nullable', 'string', 'max:120'],
            'company_website' => ['nullable', 'url:http,https', 'max:255'],
            'company_description' => ['nullable', 'string', 'max:1200'],

            'business_hours_start' => ['nullable', 'date_format:H:i'],
            'business_hours_end' => ['nullable', 'date_format:H:i'],
            'invoice_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9_-]+$/'],
            'default_tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'date_format' => ['nullable', 'string', Rule::in(['d/m/Y', 'm/d/Y', 'Y-m-d'])],
            'notifications_email' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'notifications_browser' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'automation_suggestions_enabled' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_name.required' => 'Le nom de l’entreprise est obligatoire.',
            'tenant_name.min' => 'Le nom de l’entreprise doit contenir au moins :min caractères.',
            'tenant_name.max' => 'Le nom de l’entreprise ne peut pas dépasser :max caractères.',
            'tenant_email.email' => 'Le format de l’email est invalide.',
            'tenant_timezone.required' => 'Le fuseau horaire est obligatoire.',
            'tenant_timezone.in' => 'Le fuseau horaire sélectionné est invalide.',
            'tenant_locale.required' => 'La langue est obligatoire.',
            'tenant_currency.required' => 'La devise est obligatoire.',
            'tenant_currency.in' => 'La devise sélectionnée est invalide.',
            'company_country.in' => 'Le pays sélectionné est invalide.',
            'company_website.url' => 'Le site web doit être une URL valide (https://...).',
            'company_description.max' => 'La description ne peut pas dépasser :max caractères.',
            'business_hours_start.date_format' => 'L’heure d’ouverture est invalide (format HH:MM).',
            'business_hours_end.date_format' => 'L’heure de fermeture est invalide (format HH:MM).',
            'invoice_prefix.regex' => 'Le préfixe facture doit contenir uniquement des lettres majuscules, chiffres, _ ou -.',
            'invoice_prefix.max' => 'Le préfixe facture ne peut pas dépasser :max caractères.',
            'default_tax_rate.numeric' => 'Le taux de TVA par défaut doit être numérique.',
            'default_tax_rate.between' => 'Le taux de TVA par défaut doit être entre :min et :max.',
            'date_format.in' => 'Le format de date est invalide.',
        ];
    }
}
