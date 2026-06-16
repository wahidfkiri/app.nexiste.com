<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Security\PhoneNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Services\ExtensionService;
use Vendor\Rbac\Services\TenantRoleService;

class OnboardingController extends Controller
{
    private const APP_SLUGS = [
        'clients',
        'stock',
        'invoice',
        'projects',
        'notion-workspace',
        'google-drive',
        'google-calendar',
        'google-sheets',
        'google-docx',
        'google-gmail',
    ];

    public function __construct(
        protected ExtensionService $extensions,
        protected PhoneNumberService $phoneNumbers
    )
    {
    }

    public function show(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;
        if (!$tenant) {
            return redirect('/dashboard');
        }

        if (!self::mustCompleteOnboarding($user)) {
            return redirect('/dashboard');
        }

        $this->extensions->ensureCatalogSeeded();

        $countries = collect(config('onboarding.countries', []))
            ->filter(fn ($country) => !empty($country['code']))
            ->values()
            ->all();

        $countryMap = collect($countries)->keyBy('code')->all();
        $countryCodes = array_keys($countryMap);

        $currencies = (array) config('onboarding.currencies', []);
        $currencyCodes = array_keys($currencies);
        $timezoneOptions = timezone_identifiers_list();

        $isOwner = self::isOwner($user);
        $tenantCompleted = self::isCompletedForTenant((int) $tenant->id);
        $sectors = config('onboarding.sectors', []);
        $sector = $this->getSetting((int) $tenant->id, 'onboarding_sector');
        $recommended = $this->recommendedAppsForSector($sector);

        $country = $this->normalizeCountryCode(
            $this->getSetting((int) $tenant->id, 'company_country') ?: 'FR',
            $countryCodes,
            'FR'
        );
        $phoneCountry = $this->normalizeCountryCode(
            $this->getSetting((int) $tenant->id, 'company_phone_country') ?: $country,
            $countryCodes,
            $country
        );
        $defaultTimezone = (string) ($countryMap[$country]['timezone'] ?? 'Europe/Paris');
        $defaultCurrency = (string) ($countryMap[$country]['currency'] ?? 'EUR');

        $companySetup = [
            'company_country' => $country,
            'company_phone_country' => $phoneCountry,
            'company_postal_code' => (string) ($this->getSetting((int) $tenant->id, 'company_postal_code') ?: ''),
            'company_city' => (string) ($this->getSetting((int) $tenant->id, 'company_city') ?: ''),
            'company_description' => (string) ($this->getSetting((int) $tenant->id, 'company_description') ?: ''),
            'company_website' => (string) ($this->getSetting((int) $tenant->id, 'company_website') ?: ''),
            'company_phone_local' => (string) ($this->getSetting((int) $tenant->id, 'company_phone_local') ?: ''),
            'company_timezone' => (string) ($tenant->timezone ?: $defaultTimezone),
            'company_currency' => in_array((string) $tenant->currency, $currencyCodes, true)
                ? (string) $tenant->currency
                : $defaultCurrency,
        ];

        $apps = Extension::query()
            ->whereIn('slug', self::APP_SLUGS)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $activeSlugs = TenantExtension::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->with('extension:id,slug')
            ->get()
            ->pluck('extension.slug')
            ->filter()
            ->values()
            ->all();

        return view('onboarding.index', [
            'user' => $user,
            'tenant' => $tenant,
            'isOwner' => $isOwner,
            'isInvited' => !$isOwner,
            'tenantCompleted' => $tenantCompleted,
            'invitedByName' => optional($user->invitedByUser)->name,
            'sectors' => $sectors,
            'selectedSector' => $sector,
            'recommendedApps' => $recommended,
            'apps' => $apps,
            'activeSlugs' => $activeSlugs,
            'countries' => $countries,
            'currencies' => $currencies,
            'timezoneOptions' => $timezoneOptions,
            'companySetup' => $companySetup,
        ]);
    }

    public function saveProfile(Request $request): JsonResponse
    {
        $countriesByCode = $this->mapCountriesByCode(config('onboarding.countries', []));
        $countryCodes = array_keys($countriesByCode);

        $profilePhoneCountryRaw = trim((string) $request->input('profile_phone_country', ''));
        $request->merge([
            'profile_phone_country' => $profilePhoneCountryRaw !== '' ? strtoupper($profilePhoneCountryRaw) : null,
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\\pL\\s\'-]+$/u'],
                'last_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\\pL\\s\'-]+$/u'],
                'profile_phone_country' => ['nullable', 'string', Rule::in($countryCodes), 'required_with:phone_local'],
                'phone_local' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\\s().-]{6,30}$/'],
                'phone' => ['nullable', 'string', 'max:30', 'regex:/^\\+?[0-9\\s().-]{6,30}$/'],
                'job_title' => ['nullable', 'string', 'max:100'],
                'department' => ['nullable', 'string', 'max:100'],
            ],
            [
                'first_name.required' => $this->msg('Le pr&eacute;nom est obligatoire.'),
                'first_name.min' => $this->msg('Le pr&eacute;nom doit contenir au moins :min caract&egrave;res.'),
                'first_name.max' => $this->msg('Le pr&eacute;nom ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'first_name.regex' => $this->msg('Le pr&eacute;nom contient des caract&egrave;res non autoris&eacute;s.'),
                'last_name.required' => $this->msg('Le nom est obligatoire.'),
                'last_name.min' => $this->msg('Le nom doit contenir au moins :min caract&egrave;res.'),
                'last_name.max' => $this->msg('Le nom ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'last_name.regex' => $this->msg('Le nom contient des caract&egrave;res non autoris&eacute;s.'),
                'profile_phone_country.required_with' => $this->msg('Le pays du t&eacute;l&eacute;phone est obligatoire.'),
                'profile_phone_country.in' => $this->msg('Le pays du t&eacute;l&eacute;phone est invalide.'),
                'phone_local.max' => $this->msg('Le t&eacute;l&eacute;phone ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'phone_local.regex' => $this->msg('Le format du t&eacute;l&eacute;phone est invalide.'),
                'phone.max' => $this->msg('Le t&eacute;l&eacute;phone ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'phone.regex' => $this->msg('Le format du t&eacute;l&eacute;phone est invalide.'),
                'job_title.max' => $this->msg('Le poste ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'department.max' => $this->msg('Le d&eacute;partement ne peut pas d&eacute;passer :max caract&egrave;res.'),
            ]
        );

        $validator->after(function ($validator) use ($request, $countriesByCode): void {
            $phoneLocal = trim((string) $request->input('phone_local', ''));
            $fallbackPhone = trim((string) $request->input('phone', ''));
            if ($phoneLocal === '') {
                if ($fallbackPhone !== '' && !$this->phoneNumbers->isValidInternational($fallbackPhone)) {
                    $validator->errors()->add(
                        'phone',
                        $this->msg('Le t&eacute;l&eacute;phone est invalide. Utilisez un format international, par exemple +33612345678.')
                    );
                }
                return;
            }

            $countryCode = strtoupper(trim((string) $request->input('profile_phone_country', '')));
            if ($countryCode === '' || !array_key_exists($countryCode, $countriesByCode)) {
                return;
            }

            if ($this->phoneNumbers->hasLeadingZero($phoneLocal)) {
                $validator->errors()->add(
                    'phone_local',
                    $this->msg('Saisissez le t&eacute;l&eacute;phone sans le 0 initial (l&rsquo;indicatif est d&eacute;j&agrave; s&eacute;lectionn&eacute;).')
                );
                return;
            }

            if (!$this->isPhoneLengthAllowed($phoneLocal, $countryCode, $countriesByCode)) {
                $lengthLabel = $this->phoneLengthsLabel($countryCode, $countriesByCode);
                $validator->errors()->add(
                    'phone_local',
                    $this->msg("Le t&eacute;l&eacute;phone doit contenir {$lengthLabel} chiffres pour ce pays.")
                );
                return;
            }

            if (!$this->phoneNumbers->isValidForCountry($phoneLocal, $countryCode)) {
                $validator->errors()->add(
                    'phone_local',
                    $this->msg('Le t&eacute;l&eacute;phone est invalide pour le pays s&eacute;lectionn&eacute;.')
                );
            }
        });

        $data = $validator->validate();

        /** @var User $user */
        $user = $request->user();
        $phoneLocal = trim((string) ($data['phone_local'] ?? ''));
        $profilePhoneCountry = strtoupper(trim((string) ($data['profile_phone_country'] ?? '')));
        $fallbackPhone = trim((string) ($data['phone'] ?? ''));
        $fullPhone = null;
        $dial = (string) ($countriesByCode[$profilePhoneCountry]['dial'] ?? '');

        if ($phoneLocal !== '' && $profilePhoneCountry !== '') {
            $fullPhone = $this->phoneNumbers->normalizeToE164ForCountry($phoneLocal, $profilePhoneCountry)
                ?? trim(($dial !== '' ? ($dial . ' ') : '') . $phoneLocal);
        }

        if ($fullPhone === null && $fallbackPhone !== '') {
            $fullPhone = $this->phoneNumbers->normalizeInternational($fallbackPhone);
        }

        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        $user->update([
            'name' => $fullName,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $fullPhone,
            'job_title' => $data['job_title'] ?: null,
            'department' => $data['department'] ?: null,
        ]);

        $this->setSetting((int) $user->tenant_id, $this->userProfileKey((int) $user->id), now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => $this->msg('Profil enregistr&eacute; avec succ&egrave;s.'),
        ]);
    }

    public function saveCompany(Request $request): JsonResponse
    {
        if (!$this->isOwnerRequest($request)) {
            return response()->json(['success' => false, 'message' => $this->msg('Action r&eacute;serv&eacute;e au propri&eacute;taire.')], 403);
        }

        $countries = collect(config('onboarding.countries', []))
            ->filter(fn ($country) => !empty($country['code']))
            ->values();
        $countriesByCode = $this->mapCountriesByCode($countries->all());
        $countryCodes = array_keys($countriesByCode);
        $currencyCodes = array_keys((array) config('onboarding.currencies', []));

        $request->merge([
            'company_currency' => strtoupper((string) $request->input('company_currency', '')),
            'company_country' => strtoupper((string) $request->input('company_country', '')),
            'company_phone_country' => strtoupper((string) $request->input('company_phone_country', '')),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'company_name' => ['required', 'string', 'min:2', 'max:255'],
                'company_email' => ['required', 'email:rfc', 'max:255'],
                'company_country' => ['required', 'string', Rule::in($countryCodes)],
                'company_phone_country' => ['required', 'string', Rule::in($countryCodes)],
                'company_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9\\s().-]{6,30}$/'],
                'company_postal_code' => ['required', 'string', 'min:2', 'max:20'],
                'company_city' => ['required', 'string', 'min:2', 'max:120'],
                'company_address' => ['required', 'string', 'min:5', 'max:600'],
                'company_description' => ['nullable', 'string', 'max:1200'],
                'company_website' => ['nullable', 'url:http,https', 'max:255'],
                'company_timezone' => ['required', 'string', 'max:60', Rule::in(timezone_identifiers_list())],
                'company_currency' => ['required', 'string', Rule::in($currencyCodes)],
            ],
            [
                'company_name.required' => $this->msg('Le nom de la soci&eacute;t&eacute; est obligatoire.'),
                'company_name.min' => $this->msg('Le nom de la soci&eacute;t&eacute; doit contenir au moins :min caract&egrave;res.'),
                'company_name.max' => $this->msg('Le nom de la soci&eacute;t&eacute; ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_email.required' => $this->msg('L&rsquo;email de la soci&eacute;t&eacute; est obligatoire.'),
                'company_email.email' => $this->msg('Le format de l&rsquo;email de la soci&eacute;t&eacute; est invalide.'),
                'company_email.max' => $this->msg('L&rsquo;email de la soci&eacute;t&eacute; ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_country.required' => $this->msg('Le pays est obligatoire.'),
                'company_country.in' => $this->msg('Le pays s&eacute;lectionn&eacute; est invalide.'),
                'company_phone_country.required' => $this->msg('Le pays de t&eacute;l&eacute;phone est obligatoire.'),
                'company_phone_country.in' => $this->msg('Le pays de t&eacute;l&eacute;phone s&eacute;lectionn&eacute; est invalide.'),
                'company_phone.required' => $this->msg('Le t&eacute;l&eacute;phone est obligatoire.'),
                'company_phone.max' => $this->msg('Le t&eacute;l&eacute;phone ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_phone.regex' => $this->msg('Le format du t&eacute;l&eacute;phone est invalide.'),
                'company_postal_code.required' => $this->msg('Le code postal est obligatoire.'),
                'company_postal_code.min' => $this->msg('Le code postal doit contenir au moins :min caract&egrave;res.'),
                'company_postal_code.max' => $this->msg('Le code postal ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_city.required' => $this->msg('La ville est obligatoire.'),
                'company_city.min' => $this->msg('La ville doit contenir au moins :min caract&egrave;res.'),
                'company_city.max' => $this->msg('La ville ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_address.required' => $this->msg('L&rsquo;adresse est obligatoire.'),
                'company_address.min' => $this->msg('L&rsquo;adresse doit contenir au moins :min caract&egrave;res.'),
                'company_address.max' => $this->msg('L&rsquo;adresse ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_description.max' => $this->msg('La description ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_website.url' => $this->msg('Le site web doit &ecirc;tre une URL valide (https://...).'),
                'company_website.max' => $this->msg('Le site web ne peut pas d&eacute;passer :max caract&egrave;res.'),
                'company_timezone.required' => $this->msg('Le fuseau horaire est obligatoire.'),
                'company_timezone.in' => $this->msg('Le fuseau horaire s&eacute;lectionn&eacute; est invalide.'),
                'company_currency.required' => $this->msg('La devise est obligatoire.'),
                'company_currency.in' => $this->msg('La devise s&eacute;lectionn&eacute;e est invalide.'),
            ]
        );

        $validator->after(function ($validator) use ($request, $countriesByCode): void {
            $countryCode = strtoupper(trim((string) $request->input('company_phone_country', '')));
            $phoneLocal = trim((string) $request->input('company_phone', ''));
            if ($phoneLocal === '' || $countryCode === '') {
                return;
            }

            if ($this->phoneNumbers->hasLeadingZero($phoneLocal)) {
                $validator->errors()->add(
                    'company_phone',
                    $this->msg('Saisissez le t&eacute;l&eacute;phone sans le 0 initial (l&rsquo;indicatif est d&eacute;j&agrave; s&eacute;lectionn&eacute;).')
                );
                return;
            }

            if (!$this->isPhoneLengthAllowed($phoneLocal, $countryCode, $countriesByCode)) {
                $lengthLabel = $this->phoneLengthsLabel($countryCode, $countriesByCode);
                $validator->errors()->add(
                    'company_phone',
                    $this->msg("Le t&eacute;l&eacute;phone doit contenir {$lengthLabel} chiffres pour ce pays.")
                );
                return;
            }

            if (!$this->phoneNumbers->isValidForCountry($phoneLocal, $countryCode)) {
                $validator->errors()->add(
                    'company_phone',
                    $this->msg('Le t&eacute;l&eacute;phone est invalide pour le pays s&eacute;lectionn&eacute;.')
                );
            }
        });

        $data = $validator->validate();

        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant introuvable.'], 422);
        }

        $phoneLocal = trim((string) $data['company_phone']);
        $dial = (string) ($countriesByCode[$data['company_phone_country']]['dial'] ?? '');
        $phoneFull = $this->phoneNumbers->normalizeToE164ForCountry($phoneLocal, (string) $data['company_phone_country'])
            ?? trim(($dial !== '' ? ($dial . ' ') : '') . $phoneLocal);

        $tenant->update([
            'name' => $data['company_name'],
            'email' => $data['company_email'],
            'phone' => $phoneFull ?: null,
            'address' => $data['company_address'],
            'timezone' => $data['company_timezone'],
            'currency' => strtoupper($data['company_currency']),
        ]);

        $tenantId = (int) $tenant->id;
        $this->setSetting($tenantId, 'company_country', $data['company_country']);
        $this->setSetting($tenantId, 'company_phone_country', $data['company_phone_country']);
        $this->setSetting($tenantId, 'company_phone_local', $phoneLocal);
        $this->setSetting($tenantId, 'company_postal_code', $data['company_postal_code']);
        $this->setSetting($tenantId, 'company_city', $data['company_city']);
        $this->setSetting($tenantId, 'company_description', (string) ($data['company_description'] ?? ''));
        $this->setSetting($tenantId, 'company_website', (string) ($data['company_website'] ?? ''));
        $this->setSetting($tenantId, 'onboarding_company_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => $this->msg('Informations soci&eacute;t&eacute; enregistr&eacute;es.'),
        ]);
    }

    public function saveSector(Request $request): JsonResponse
    {
        if (!$this->isOwnerRequest($request)) {
            return response()->json(['success' => false, 'message' => $this->msg('Action r&eacute;serv&eacute;e au propri&eacute;taire.')], 403);
        }

        $allowedSectors = array_keys(config('onboarding.sectors', []));

        $data = $request->validate(
            [
                'sector' => ['required', 'string', Rule::in($allowedSectors)],
            ],
            [
                'sector.required' => $this->msg('Le secteur d&rsquo;activit&eacute; est obligatoire.'),
                'sector.in' => $this->msg('Le secteur d&rsquo;activit&eacute; s&eacute;lectionn&eacute; est invalide.'),
            ]
        );

        $tenantId = (int) $request->user()->tenant_id;
        $this->setSetting($tenantId, 'onboarding_sector', $data['sector']);
        $this->setSetting($tenantId, 'onboarding_sector_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => $this->msg('Secteur enregistr&eacute;.'),
            'recommended_apps' => $this->recommendedAppsForSector($data['sector']),
        ]);
    }

    public function saveApps(Request $request): JsonResponse
    {
        if (!$this->isOwnerRequest($request)) {
            return response()->json(['success' => false, 'message' => $this->msg('Action r&eacute;serv&eacute;e au propri&eacute;taire.')], 403);
        }

        $data = $request->validate(
            [
                'apps' => ['nullable', 'array'],
                'apps.*' => ['string', 'max:100', Rule::in(self::APP_SLUGS)],
            ],
            [
                'apps.array' => $this->msg('Le format des applications est invalide.'),
                'apps.*.max' => $this->msg('Un identifiant d&rsquo;application est trop long.'),
                'apps.*.in' => $this->msg('Une application s&eacute;lectionn&eacute;e est invalide.'),
            ]
        );

        $tenantId = (int) $request->user()->tenant_id;
        $selected = collect($data['apps'] ?? [])->unique()->values();

        $sector = $this->getSetting($tenantId, 'onboarding_sector');
        if ($selected->isEmpty()) {
            $selected = collect($this->recommendedAppsForSector($sector));
        }

        $this->extensions->ensureCatalogSeeded();

        $available = Extension::query()
            ->whereIn('slug', self::APP_SLUGS)
            ->pluck('id', 'slug');

        DB::transaction(function () use ($tenantId, $selected, $available, $request): void {
            foreach ($available as $slug => $extensionId) {
                $status = $selected->contains($slug) ? 'active' : 'inactive';

                TenantExtension::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'extension_id' => (int) $extensionId],
                    [
                        'status' => $status,
                        'activated_by' => (int) $request->user()->id,
                        'activated_at' => $status === 'active' ? now() : null,
                        'deactivated_at' => $status === 'inactive' ? now() : null,
                    ]
                );
            }
        });

        $this->setSetting($tenantId, 'onboarding_apps_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => $this->msg('Applications configur&eacute;es.'),
            'active_slugs' => $selected->values()->all(),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        if (self::isOwner($user)) {
            $this->promoteBootstrapAdminToOwner($user);
            $this->setSetting($tenantId, 'onboarding_completed_at', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => $this->msg('Configuration termin&eacute;e avec succ&egrave;s.'),
                'redirect' => url('/dashboard'),
                'waiting_owner' => false,
            ]);
        }

        $this->setSetting($tenantId, $this->userCompletionKey((int) $user->id), now()->toDateTimeString());
        $tenantCompleted = self::isCompletedForTenant($tenantId);

        return response()->json([
            'success' => true,
            'message' => $tenantCompleted
                ? $this->msg('Configuration personnelle termin&eacute;e avec succ&egrave;s.')
                : $this->msg('Profil finalis&eacute;. En attente de la finalisation du propri&eacute;taire.'),
            'redirect' => $tenantCompleted ? url('/dashboard') : route('onboarding.show'),
            'waiting_owner' => !$tenantCompleted,
        ]);
    }

    public static function isCompletedForTenant(int $tenantId): bool
    {
        return TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', 'onboarding_completed_at')
            ->whereNotNull('value')
            ->exists();
    }

    public static function isOwner(User $user): bool
    {
        if ((bool) $user->is_tenant_owner || (string) $user->role_in_tenant === 'owner') {
            return true;
        }

        return self::isBootstrapAdminWithoutTenantOwner($user);
    }

    public static function isCompletedForUser(User $user): bool
    {
        if (!$user->tenant_id) {
            return true;
        }

        $tenantId = (int) $user->tenant_id;
        $tenantCompleted = self::isCompletedForTenant($tenantId);

        if (self::isOwner($user)) {
            return $tenantCompleted;
        }

        if (!$tenantCompleted) {
            return false;
        }

        return TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', self::userCompletionKeyStatic((int) $user->id))
            ->whereNotNull('value')
            ->exists();
    }

    public static function mustCompleteOnboarding(User $user): bool
    {
        return !self::isCompletedForUser($user);
    }

    private function isOwnerRequest(Request $request): bool
    {
        /** @var User $user */
        $user = $request->user();
        return self::isOwner($user);
    }

    private function promoteBootstrapAdminToOwner(User $user): void
    {
        if ((bool) $user->is_tenant_owner || (string) $user->role_in_tenant === 'owner') {
            return;
        }

        if (!self::isBootstrapAdminWithoutTenantOwner($user)) {
            return;
        }

        $tenantId = (int) $user->tenant_id;
        if ($tenantId <= 0) {
            return;
        }

        app(TenantRoleService::class)->syncUserRole($user, $tenantId, 'owner', [
            'role_in_tenant' => 'owner',
            'is_tenant_owner' => true,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $user->forceFill([
            'role_in_tenant' => 'owner',
            'is_tenant_owner' => true,
            'status' => 'active',
            'is_active' => true,
        ])->save();
    }

    private static function isBootstrapAdminWithoutTenantOwner(User $user): bool
    {
        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return false;
        }

        $role = (string) ($user->role_in_tenant ?? '');
        $membership = method_exists($user, 'membershipForTenant') ? $user->membershipForTenant($tenantId) : null;
        if ($membership) {
            $role = (string) ($membership->role_in_tenant ?: $role);
        }

        if ($role !== 'admin') {
            return false;
        }

        return !self::tenantHasOwner($tenantId);
    }

    private static function tenantHasOwner(int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        if (Schema::hasTable('tenant_user_memberships')) {
            $membershipQuery = DB::table('tenant_user_memberships')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->where('is_tenant_owner', true)
                        ->orWhere('role_in_tenant', 'owner');
                });

            if ($membershipQuery->exists()) {
                return true;
            }
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'tenant_id')) {
            return false;
        }

        $userQuery = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where(function ($query): void {
                $query->where('is_tenant_owner', true)
                    ->orWhere('role_in_tenant', 'owner');
            });

        if (Schema::hasColumn('users', 'deleted_at')) {
            $userQuery->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'status')) {
            $userQuery->where('status', 'active');
        } elseif (Schema::hasColumn('users', 'is_active')) {
            $userQuery->where('is_active', true);
        }

        return $userQuery->exists();
    }

    private function normalizeCountryCode(string $value, array $allowed, string $fallback): string
    {
        $upper = strtoupper(trim($value));
        return in_array($upper, $allowed, true) ? $upper : $fallback;
    }

    private function recommendedAppsForSector(?string $sector): array
    {
        $defaults = config('onboarding.defaults_by_sector', []);
        if (!$sector || !array_key_exists($sector, $defaults)) {
            return ['clients', 'invoice', 'projects', 'notion-workspace'];
        }

        return $defaults[$sector];
    }

    private function getSetting(int $tenantId, string $key): ?string
    {
        return TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->value('value');
    }

    private function setSetting(int $tenantId, string $key, string $value): void
    {
        TenantSetting::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }

    private function userProfileKey(int $userId): string
    {
        return 'onboarding_user_' . $userId . '_profile_done';
    }

    private function userCompletionKey(int $userId): string
    {
        return self::userCompletionKeyStatic($userId);
    }

    private static function userCompletionKeyStatic(int $userId): string
    {
        return 'onboarding_user_' . $userId . '_completed_at';
    }

    private function mapCountriesByCode(array $countries): array
    {
        $mapped = [];

        foreach ($countries as $country) {
            $code = strtoupper(trim((string) ($country['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            $mapped[$code] = [
                'dial' => (string) ($country['dial'] ?? ''),
                'phone_lengths' => collect($country['phone_lengths'] ?? [])
                    ->map(fn ($length) => (int) $length)
                    ->filter(fn ($length) => $length > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ];
        }

        return $mapped;
    }

    private function isPhoneLengthAllowed(string $phoneValue, string $countryCode, array $countriesByCode): bool
    {
        $digitsLength = strlen($this->phoneDigits($phoneValue));
        if ($digitsLength === 0) {
            return true;
        }

        $code = strtoupper(trim($countryCode));
        $allowed = $countriesByCode[$code]['phone_lengths'] ?? [];
        if (empty($allowed)) {
            return $digitsLength >= 8 && $digitsLength <= 15;
        }

        return in_array($digitsLength, $allowed, true);
    }

    private function phoneLengthsLabel(string $countryCode, array $countriesByCode): string
    {
        $code = strtoupper(trim($countryCode));
        $allowed = $countriesByCode[$code]['phone_lengths'] ?? [];

        if (empty($allowed)) {
            return 'entre 8 et 15';
        }

        if (count($allowed) === 1) {
            return (string) $allowed[0];
        }

        $last = array_pop($allowed);
        return implode(', ', $allowed) . ' ou ' . $last;
    }

    private function phoneDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function msg(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
