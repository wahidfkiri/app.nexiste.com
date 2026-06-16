@extends('layouts.onboarding')

@section('title', 'Finalisation de votre espace')

@php
    $steps = $isOwner
        ? ['Profil', 'Société', 'Secteur', 'Applications']
        : ['Profil'];

    $initialApps = !empty($activeSlugs) ? $activeSlugs : $recommendedApps;

    $fallbackIcons = [
        'clients' => 'fa-users',
        'stock' => 'fa-boxes-stacked',
        'invoice' => 'fa-file-invoice',
        'projects' => 'fa-diagram-project',
        'notion-workspace' => 'fa-note-sticky',
        'google-drive' => 'fab fa-google-drive',
        'google-calendar' => 'fa-calendar-days',
        'google-sheets' => 'fa-table',
        'google-docx' => 'fa-file-lines',
        'google-gmail' => 'fa-envelope',
    ];

    $profilePhoneRaw = (string) old('phone', $user->phone ?? '');
    $profilePhoneCountry = (string) old('profile_phone_country', $companySetup['company_phone_country'] ?? 'FR');
    $profilePhoneLocal = (string) old('phone_local', $profilePhoneRaw);

    if (!old('phone_local') && $profilePhoneRaw !== '') {
        $sortedCountries = collect($countries)->sortByDesc(fn ($country) => strlen((string) ($country['dial'] ?? '')));
        foreach ($sortedCountries as $country) {
            $dial = (string) ($country['dial'] ?? '');
            if ($dial !== '' && str_starts_with(trim($profilePhoneRaw), $dial)) {
                $profilePhoneCountry = (string) ($country['code'] ?? $profilePhoneCountry);
                $profilePhoneLocal = trim(substr(trim($profilePhoneRaw), strlen($dial)));
                break;
            }
        }
    }
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
  .ob-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 34px 16px;
  }

  .ob-card {
    width: min(1180px, 100%);
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 24px;
    box-shadow: 0 28px 64px rgba(15, 23, 42, 0.12);
    overflow: visible;
  }

  .ob-head {
    padding: 30px 34px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(110deg, #ffffff 0%, #f8fafc 70%, #eef2ff 100%);
  }

  .ob-head h1 {
    margin: 0;
    font-size: clamp(1.4rem, 1.8vw, 2rem);
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
  }

  .ob-head p {
    margin: 8px 0 0;
    color: #475569;
    font-size: 0.99rem;
  }

  .ob-steps-wrap {
    padding: 14px 34px 12px;
    border-bottom: 1px solid #eef2ff;
  }

  .ob-steps {
    display: flex;
    align-items: center;
    gap: 24px;
    overflow-x: auto;
    padding-bottom: 4px;
  }

  .ob-step {
    border: 0;
    border-radius: 0;
    padding: 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    background: transparent;
    font-weight: 600;
    font-size: 0.9rem;
    transition: .2s ease;
    position: relative;
    flex: 0 0 auto;
    white-space: nowrap;
  }

  .ob-step:not(:last-child)::after {
    content: "";
    width: 22px;
    height: 1px;
    background: #cbd5e1;
    margin-left: 14px;
  }

  .ob-step-index {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex: 0 0 24px;
  }

  .ob-step.active {
    color: #1d4ed8;
  }

  .ob-step.active .ob-step-index {
    border-color: #3b82f6;
    background: #3b82f6;
    color: #fff;
  }

  .ob-step.done {
    color: #0f766e;
  }

  .ob-step.done .ob-step-index {
    border-color: #14b8a6;
    background: #14b8a6;
    color: #fff;
  }

  .ob-progress-track {
    height: 8px;
    border-radius: 99px;
    background: #e2e8f0;
    margin-top: 14px;
    overflow: hidden;
  }

  .ob-progress-value {
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #2563eb, #0ea5e9);
    transition: width .3s ease;
  }

  .ob-body {
    padding: 26px 34px 28px;
  }

  .ob-note {
    margin: 0 0 16px;
    color: #64748b;
    font-size: 0.9rem;
  }

  .required-mark {
    color: #dc2626;
    font-weight: 700;
    margin-left: 3px;
  }

  .ob-panel {
    display: none;
    animation: obFade .25s ease;
  }

  .ob-panel.active {
    display: block;
  }

  @keyframes obFade {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .ob-panel h2 {
    margin: 0 0 6px;
    font-size: 1.2rem;
    color: #0f172a;
  }

  .ob-panel p {
    margin: 0 0 18px;
    color: #64748b;
  }

  .ob-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .ob-grid.full {
    grid-template-columns: 1fr;
  }

  .ob-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .ob-field label {
    color: #0f172a;
    font-size: .91rem;
    font-weight: 700;
  }

  .ob-input,
  .ob-textarea,
  .ob-select {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #fff;
    padding: 11px 13px;
    font-size: 0.95rem;
    color: #0f172a;
    transition: border-color .18s ease, box-shadow .18s ease;
  }

  .ob-input:focus,
  .ob-textarea:focus,
  .ob-select:focus {
    outline: 0;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  }

  .ob-textarea {
    min-height: 108px;
    resize: vertical;
  }

  .ob-input.is-invalid,
  .ob-textarea.is-invalid,
  .ob-select.is-invalid,
  .ts-wrapper.is-invalid .ts-control,
  .ob-phone-wrap.is-invalid,
  .ob-sector-grid.is-invalid,
  .ob-app-grid.is-invalid {
    border-color: #dc2626 !important;
  }

  .form-error {
    color: #b91c1c;
    font-size: 0.83rem;
    margin-top: -2px;
    display: inline-block;
  }

  .ts-wrapper {
    width: 100%;
  }

  .ts-wrapper .ts-control {
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    min-height: 44px;
    padding: 8px 12px;
    box-shadow: none;
    background: #fff !important;
    opacity: 1 !important;
  }

  .ts-wrapper.single.input-active .ts-control,
  .ts-wrapper.single.has-items .ts-control {
    background: #fff !important;
  }

  .ts-wrapper.focus .ts-control {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  }

  .ts-wrapper.single .ts-control input {
    font-size: .94rem;
    background: #fff !important;
    opacity: 1 !important;
  }

  .ts-dropdown {
    border: 1px solid #dbeafe;
    border-radius: 12px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    background: #fff !important;
    opacity: 1 !important;
    z-index: 3200 !important;
  }

  .ts-dropdown .option,
  .ts-dropdown .active {
    background: #fff !important;
    opacity: 1 !important;
  }

  .country-option,
  .country-item {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .country-option img,
  .country-item img {
    width: 20px;
    height: 15px;
    border-radius: 3px;
    border: 1px solid #dbeafe;
    object-fit: cover;
    flex: 0 0 20px;
  }

  .country-option strong,
  .country-item strong {
    font-size: .92rem;
    color: #0f172a;
    font-weight: 700;
  }

  .country-option span,
  .country-item span {
    color: #64748b;
    font-size: .82rem;
  }

  .ob-phone-wrap {
    display: grid;
    grid-template-columns: 210px 1fr;
    gap: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px;
    background: #f8fafc;
  }

  .ob-phone-wrap .ob-field {
    gap: 0;
  }

  .ob-phone-wrap .ob-input,
  .ob-phone-wrap .ob-select {
    background: #fff;
  }

  .ob-mini {
    margin-top: -3px;
    color: #64748b;
    font-size: 0.78rem;
  }

  .ob-sector-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px;
    background: #f8fafc;
  }

  .ob-sector-card {
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    padding: 12px 14px;
    transition: .18s ease;
    font-weight: 700;
    color: #1e293b;
  }

  .ob-sector-card small {
    display: block;
    margin-top: 3px;
    font-weight: 500;
    color: #64748b;
  }

  .ob-sector-card input {
    display: none;
  }

  .ob-sector-card.active {
    border-color: #3b82f6;
    background: linear-gradient(130deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.16);
  }

  .ob-app-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px;
    background: #f8fafc;
  }

  .ob-app-card {
    position: relative;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    background: #fff;
    padding: 12px;
    cursor: pointer;
    transition: .18s ease;
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 122px;
  }

  .ob-app-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .ob-app-head {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .ob-app-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #0f172a;
    font-size: 1rem;
    background: #e2e8f0;
    overflow: hidden;
  }

  .ob-app-icon img {
    width: 20px;
    height: 20px;
    object-fit: contain;
  }

  .ob-app-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
  }

  .ob-app-tagline {
    font-size: 0.83rem;
    color: #64748b;
    margin: 0;
  }

  .ob-app-card.active {
    border-color: #2563eb;
    background: linear-gradient(130deg, #eff6ff 0%, #ffffff 100%);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
  }

  .ob-app-card.active::after {
    content: "Installée";
    position: absolute;
    right: 10px;
    top: 10px;
    font-size: .72rem;
    font-weight: 700;
    color: #1d4ed8;
    background: #dbeafe;
    border-radius: 999px;
    padding: 4px 8px;
  }

  .ob-alert {
    margin: 0 0 14px;
    border-radius: 12px;
    padding: 11px 13px;
    font-size: 0.91rem;
    border: 1px solid transparent;
  }

  .ob-alert.hidden {
    display: none;
  }

  .ob-alert.success {
    color: #065f46;
    border-color: #99f6e4;
    background: #ecfdf5;
  }

  .ob-alert.error {
    color: #991b1b;
    border-color: #fecaca;
    background: #fef2f2;
  }

  .ob-footer {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px dashed #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .ob-footer-right {
    display: flex;
    gap: 10px;
    margin-left: auto;
  }

  .ob-footer .btn {
    min-width: 128px;
    justify-content: center;
    font-weight: 700;
    border-radius: 10px;
  }

  .ob-install-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1100;
    padding: 18px;
  }

  .ob-install-overlay.open {
    display: flex;
  }

  .ob-install-card {
    width: min(520px, 100%);
    border-radius: 18px;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
    padding: 24px;
    text-align: center;
  }

  .ob-install-card h3 {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 1.25rem;
  }

  .ob-install-card p {
    margin: 0 0 16px;
    color: #64748b;
    font-size: .94rem;
  }

  .ob-install-bar-track {
    height: 13px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
  }

  .ob-install-bar {
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #2563eb 0%, #06b6d4 100%);
    transition: width .25s ease;
  }

  .ob-install-value {
    margin-top: 12px;
    color: #1d4ed8;
    font-size: 1.4rem;
    font-weight: 800;
  }

  @media (max-width: 900px) {
    .ob-head, .ob-steps-wrap, .ob-body {
      padding-left: 18px;
      padding-right: 18px;
    }

    .ob-grid {
      grid-template-columns: 1fr;
    }

    .ob-phone-wrap {
      grid-template-columns: 1fr;
    }

    .ob-steps {
      gap: 12px;
    }

    .ob-step:not(:last-child)::after {
      width: 14px;
      margin-left: 8px;
    }
  }
</style>
@endpush

@section('content')
<div class="ob-shell">
  <div class="ob-card" id="onboardingWizard"
       data-is-owner="{{ $isOwner ? '1' : '0' }}"
       data-complete-url="{{ route('onboarding.complete') }}"
       data-profile-url="{{ route('onboarding.profile') }}"
       data-company-url="{{ route('onboarding.company') }}"
       data-sector-url="{{ route('onboarding.sector') }}"
       data-apps-url="{{ route('onboarding.apps') }}">
    <div class="ob-head">
      <h1>Finalisez votre espace CRM</h1>
      <p>Quelques étapes simples pour lancer votre activité dans un espace prêt à l'emploi.</p>
    </div>

    <div class="ob-steps-wrap">
      <div class="ob-steps" id="wizardSteps">
        @foreach($steps as $index => $step)
          <div class="ob-step {{ $index === 0 ? 'active' : '' }}" data-step-nav="{{ $index }}">
            <span class="ob-step-index">{{ $index + 1 }}</span>
            <span>{{ $step }}</span>
          </div>
        @endforeach
      </div>
      <div class="ob-progress-track">
        <div class="ob-progress-value" id="wizardProgress"></div>
      </div>
    </div>

    <div class="ob-body">
      <div class="ob-alert hidden" id="wizardAlert"></div>
      <p class="ob-note">Les champs marqués <span class="required-mark">*</span> sont obligatoires.</p>

      <section class="ob-panel active" data-step-panel="0">
        <h2>Votre profil</h2>
        <p>Ajoutez vos informations pour personnaliser votre compte.</p>

        <form id="profileForm" novalidate>
          <div class="ob-grid">
            <div class="ob-field">
              <label for="firstName">Prénom <span class="required-mark">*</span></label>
              <input type="text" class="ob-input" id="firstName" name="first_name"
                     data-label="Prénom" required minlength="2"
                     value="{{ old('first_name', $user->first_name ?? '') }}">
            </div>

            <div class="ob-field">
              <label for="lastName">Nom <span class="required-mark">*</span></label>
              <input type="text" class="ob-input" id="lastName" name="last_name"
                     data-label="Nom" required minlength="2"
                     value="{{ old('last_name', $user->last_name ?? '') }}">
            </div>

            <div class="ob-field">
              <label>Téléphone</label>
              <div class="ob-phone-wrap" id="profilePhoneWrap">
                <div class="ob-field">
                  <select class="ob-select js-phone-country-select" id="profilePhoneCountry" name="profile_phone_country" data-label="Pays du téléphone" data-placeholder="Indicatif...">
                    @foreach($countries as $country)
                      <option value="{{ $country['code'] }}"
                              data-code="{{ $country['code'] }}"
                              data-name="{{ $country['name'] }}"
                              data-dial="{{ $country['dial'] }}"
                              {{ $profilePhoneCountry === $country['code'] ? 'selected' : '' }}>
                        {{ $country['code'] }} {{ $country['dial'] }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="ob-field">
                  <input type="text" class="ob-input" id="phone" name="phone_local"
                         data-label="Téléphone" pattern="^[0-9\s().-]{6,30}$"
                         value="{{ $profilePhoneLocal }}">
                  <span class="ob-mini" id="profilePhonePrefixHelper"></span>
                </div>
              </div>
            </div>

            <div class="ob-field">
              <label for="jobTitle">Poste</label>
              <input type="text" class="ob-input" id="jobTitle" name="job_title"
                     data-label="Poste"
                     value="{{ old('job_title', $user->job_title ?? '') }}">
            </div>

            <div class="ob-field">
              <label for="department">Département</label>
              <input type="text" class="ob-input" id="department" name="department"
                     data-label="Département"
                     value="{{ old('department', $user->department ?? '') }}">
            </div>
          </div>
        </form>
      </section>

      @if($isOwner)
        <section class="ob-panel" data-step-panel="1">
          <h2>Informations de la société</h2>
          <p>Configurez votre entreprise pour une expérience CRM adaptée.</p>

          <form id="companyForm" novalidate>
            <div class="ob-grid">
              <div class="ob-field">
                <label for="companyName">Nom de la société <span class="required-mark">*</span></label>
                <input type="text" class="ob-input" id="companyName" name="company_name"
                       data-label="Nom de la société" required minlength="2"
                       value="{{ old('company_name', $tenant->name ?? '') }}">
              </div>

              <div class="ob-field">
                <label for="companyEmail">Email société <span class="required-mark">*</span></label>
                <input type="email" class="ob-input" id="companyEmail" name="company_email"
                       data-label="Email société" required
                       value="{{ old('company_email', $tenant->email ?? $user->email) }}">
              </div>

              <div class="ob-field">
                <label for="companyCountry">Pays <span class="required-mark">*</span></label>
                <select class="ob-select js-country-select" id="companyCountry" name="company_country" data-label="Pays" data-placeholder="Rechercher un pays..." required>
                  @foreach($countries as $country)
                    <option value="{{ $country['code'] }}"
                            data-code="{{ $country['code'] }}"
                            data-name="{{ $country['name'] }}"
                            data-dial="{{ $country['dial'] }}"
                            data-timezone="{{ $country['timezone'] }}"
                            data-currency="{{ $country['currency'] }}"
                            {{ old('company_country', $companySetup['company_country']) === $country['code'] ? 'selected' : '' }}>
                      {{ $country['name'] }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="ob-field">
                <label>Téléphone société <span class="required-mark">*</span></label>
                <div class="ob-phone-wrap" id="phoneWrap">
                  <div class="ob-field">
                    <select class="ob-select js-phone-country-select" id="companyPhoneCountry" name="company_phone_country" data-label="Pays de téléphone" data-placeholder="Indicatif..." required>
                      @foreach($countries as $country)
                        <option value="{{ $country['code'] }}"
                                data-code="{{ $country['code'] }}"
                                data-name="{{ $country['name'] }}"
                                data-dial="{{ $country['dial'] }}"
                                {{ old('company_phone_country', $companySetup['company_phone_country']) === $country['code'] ? 'selected' : '' }}>
                          {{ $country['code'] }} {{ $country['dial'] }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="ob-field">
                    <input type="text" class="ob-input" id="companyPhone" name="company_phone"
                           data-label="Téléphone société" required pattern="^[0-9\s().-]{6,30}$"
                           value="{{ old('company_phone', $companySetup['company_phone_local']) }}">
                    <span class="ob-mini" id="phonePrefixHelper"></span>
                  </div>
                </div>
              </div>

              <div class="ob-field">
                <label for="companyPostalCode">Code postal <span class="required-mark">*</span></label>
                <input type="text" class="ob-input" id="companyPostalCode" name="company_postal_code"
                       data-label="Code postal" required minlength="2"
                       value="{{ old('company_postal_code', $companySetup['company_postal_code']) }}">
              </div>

              <div class="ob-field">
                <label for="companyCity">Ville <span class="required-mark">*</span></label>
                <input type="text" class="ob-input" id="companyCity" name="company_city"
                       data-label="Ville" required minlength="2"
                       value="{{ old('company_city', $companySetup['company_city']) }}">
              </div>

              <div class="ob-field" style="grid-column: 1 / -1;">
                <label for="companyAddress">Adresse complète <span class="required-mark">*</span></label>
                <textarea class="ob-textarea" id="companyAddress" name="company_address"
                          data-label="Adresse complète" required minlength="5">{{ old('company_address', $tenant->address ?? '') }}</textarea>
              </div>

              <div class="ob-field" style="grid-column: 1 / -1;">
                <label for="companyDescription">Description de la société</label>
                <textarea class="ob-textarea" id="companyDescription" name="company_description"
                          data-label="Description de la société">{{ old('company_description', $companySetup['company_description']) }}</textarea>
              </div>

              <div class="ob-field">
                <label for="companyWebsite">Site web</label>
                <input type="url" class="ob-input" id="companyWebsite" name="company_website"
                       data-label="Site web"
                       value="{{ old('company_website', $companySetup['company_website']) }}"
                       placeholder="https://votre-site.com">
              </div>

              <div class="ob-field">
                <label for="companyCurrency">Devise <span class="required-mark">*</span></label>
                <select class="ob-select" id="companyCurrency" name="company_currency" data-label="Devise" required>
                  @foreach($currencies as $code => $label)
                    <option value="{{ $code }}" {{ old('company_currency', $companySetup['company_currency']) === $code ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="ob-field" style="grid-column: 1 / -1;">
                <label for="companyTimezone">Fuseau horaire <span class="required-mark">*</span></label>
                <select class="ob-select" id="companyTimezone" name="company_timezone" data-label="Fuseau horaire" required>
                  @foreach($timezoneOptions as $tz)
                    <option value="{{ $tz }}" {{ old('company_timezone', $companySetup['company_timezone']) === $tz ? 'selected' : '' }}>
                      {{ $tz }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </form>
        </section>

        <section class="ob-panel" data-step-panel="2">
          <h2>Votre secteur d'activité</h2>
          <p>Nous adaptons les applications et recommandations selon votre domaine.</p>

          <form id="sectorForm" novalidate>
            <div class="ob-field" id="sectorField">
              <div class="ob-sector-grid" id="sectorGrid">
                @foreach($sectors as $key => $label)
                  <label class="ob-sector-card {{ $selectedSector === $key ? 'active' : '' }}" data-sector-card>
                    <input type="radio" name="sector" value="{{ $key }}" {{ $selectedSector === $key ? 'checked' : '' }}>
                    {{ $label }}
                    <small>Configuration optimisée pour {{ strtolower($label) }}</small>
                  </label>
                @endforeach
              </div>
            </div>
          </form>
        </section>

        <section class="ob-panel" data-step-panel="3">
          <h2>Applications à installer</h2>
          <p>Sélectionnez les applications nécessaires. Vous pourrez en ajouter d'autres plus tard.</p>

          <form id="appsForm" novalidate>
            <div class="ob-field" id="appsField">
              <div class="ob-app-grid" id="appsGrid">
                @foreach($apps as $app)
                  @php
                    $icon = trim((string) ($app->icon ?? ''));
                    $iconClass = null;
                    $iconUrl = $app->icon_url;

                    if ($icon !== '') {
                        if (str_starts_with($icon, 'fa-')) {
                            $iconClass = 'fa-solid ' . $icon;
                        } elseif (
                            str_starts_with($icon, 'fas ')
                            || str_starts_with($icon, 'far ')
                            || str_starts_with($icon, 'fab ')
                            || str_starts_with($icon, 'fa ')
                        ) {
                            $iconClass = $icon;
                        }
                    }

                    if (!$iconClass && !$iconUrl && isset($fallbackIcons[$app->slug])) {
                        $fallback = $fallbackIcons[$app->slug];
                        $iconClass = str_starts_with($fallback, 'fa-') ? 'fa-solid ' . $fallback : $fallback;
                    }
                  @endphp

                  <label class="ob-app-card {{ in_array($app->slug, $initialApps, true) ? 'active' : '' }}" data-app-card>
                    <input type="checkbox" name="apps[]" value="{{ $app->slug }}" {{ in_array($app->slug, $initialApps, true) ? 'checked' : '' }}>
                    <div class="ob-app-head">
                      <span class="ob-app-icon" style="background: {{ $app->icon_bg_color ?: '#e2e8f0' }}">
                        @if($iconUrl)
                          <img src="{{ $iconUrl }}" alt="{{ $app->name }}">
                        @elseif($iconClass)
                          <i class="{{ $iconClass }}"></i>
                        @else
                          <i class="fa-solid fa-puzzle-piece"></i>
                        @endif
                      </span>
                      <span class="ob-app-name">{{ $app->name }}</span>
                    </div>
                    <p class="ob-app-tagline">{{ $app->tagline ?: 'Application métier intégrée à votre CRM.' }}</p>
                  </label>
                @endforeach
              </div>
            </div>
          </form>
        </section>
      @endif

      <div class="ob-footer">
        <span class="ob-note" id="wizardStepText">Étape 1 sur {{ count($steps) }}</span>
        <div class="ob-footer-right">
          <button type="button" class="btn btn-secondary" id="prevBtn" style="display:none;"><i class="fas fa-arrow-left"></i> Précédent</button>
          <button type="button" class="btn btn-primary" id="nextBtn">Suivant <i class="fas fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="ob-install-overlay" id="installOverlay">
  <div class="ob-install-card">
    <h3>Installation de votre espace</h3>
    <p id="installStatus">Initialisation de votre environnement CRM...</p>
    <div class="ob-install-bar-track">
      <div class="ob-install-bar" id="installBar"></div>
    </div>
    <div class="ob-install-value" id="installValue">0%</div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const wizard = document.getElementById('onboardingWizard');
    if (!wizard) {
      return;
    }

    const isOwner = wizard.dataset.isOwner === '1';
    const completeUrl = wizard.dataset.completeUrl;
    const profileUrl = wizard.dataset.profileUrl;
    const companyUrl = wizard.dataset.companyUrl;
    const sectorUrl = wizard.dataset.sectorUrl;
    const appsUrl = wizard.dataset.appsUrl;

    const stepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
    const stepNav = Array.from(document.querySelectorAll('[data-step-nav]'));
    const progressBar = document.getElementById('wizardProgress');
    const stepText = document.getElementById('wizardStepText');
    const alertBox = document.getElementById('wizardAlert');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    const installOverlay = document.getElementById('installOverlay');
    const installBar = document.getElementById('installBar');
    const installValue = document.getElementById('installValue');
    const installStatus = document.getElementById('installStatus');

    const countries = @json($countries);
    const countriesMap = countries.reduce((carry, item) => {
      carry[item.code] = item;
      return carry;
    }, {});

    let currentStep = 0;
    const totalSteps = stepPanels.length;
    let installTimer = null;

    const stepConfig = isOwner
      ? [
          { formId: 'profileForm', url: profileUrl },
          { formId: 'companyForm', url: companyUrl },
          { formId: 'sectorForm', url: sectorUrl },
          { formId: 'appsForm', url: appsUrl, isFinalSelection: true },
        ]
      : [
          { formId: 'profileForm', url: profileUrl, isFinalSelection: true },
        ];

    const companyCountry = document.getElementById('companyCountry');
    const profilePhoneCountry = document.getElementById('profilePhoneCountry');
    const profilePhonePrefixHelper = document.getElementById('profilePhonePrefixHelper');
    const companyPhoneCountry = document.getElementById('companyPhoneCountry');
    const companyPhonePrefixHelper = document.getElementById('phonePrefixHelper');
    const companyTimezone = document.getElementById('companyTimezone');
    const companyCurrency = document.getElementById('companyCurrency');
    const tomSelectInstances = {};

    function flagUrl(code) {
      if (!code) {
        return '';
      }
      return `https://flagcdn.com/24x18/${String(code).toLowerCase()}.png`;
    }

    function refreshPhoneHelper(selectElement, helperElement) {
      if (!selectElement || !helperElement) {
        return;
      }
      const phoneCode = selectElement.value;
      const phoneData = countriesMap[phoneCode] || null;
      helperElement.textContent = phoneData ? `Indicatif: ${phoneData.dial}` : '';
    }

    function phoneDigits(value) {
      return String(value || '').replace(/\D+/g, '');
    }

    function allowedPhoneLengths(countryCode) {
      const country = countriesMap[String(countryCode || '').toUpperCase()] || null;
      if (!country || !Array.isArray(country.phone_lengths)) {
        return [];
      }
      return country.phone_lengths
        .map((length) => Number(length))
        .filter((length) => Number.isInteger(length) && length > 0);
    }

    function phoneLengthsLabel(lengths) {
      if (!Array.isArray(lengths) || lengths.length === 0) {
        return 'entre 8 et 15';
      }
      if (lengths.length === 1) {
        return String(lengths[0]);
      }
      const head = lengths.slice(0, -1).join(', ');
      return `${head} ou ${lengths[lengths.length - 1]}`;
    }

    function syncTomSelectErrorState() {
      [companyCountry, profilePhoneCountry, companyPhoneCountry].forEach((select) => {
        if (!select) {
          return;
        }
        const wrapper = select.closest('.ts-wrapper');
        if (wrapper) {
          wrapper.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
        }
      });

      const profilePhoneWrap = document.getElementById('profilePhoneWrap');
      if (profilePhoneWrap) {
        const profileHasError = Boolean(
          profilePhoneCountry?.classList.contains('is-invalid')
          || document.querySelector('[name="phone_local"]')?.classList.contains('is-invalid')
        );
        profilePhoneWrap.classList.toggle('is-invalid', profileHasError);
      }

      const companyPhoneWrap = document.getElementById('phoneWrap');
      if (companyPhoneWrap) {
        const companyHasError = Boolean(
          companyPhoneCountry?.classList.contains('is-invalid')
          || document.querySelector('[name="company_phone"]')?.classList.contains('is-invalid')
        );
        companyPhoneWrap.classList.toggle('is-invalid', companyHasError);
      }
    }

    function initCountryTomSelect(select, mode = 'country') {
      if (!select || !window.TomSelect) {
        return null;
      }
      return new TomSelect(select, {
        create: false,
        allowEmptyOption: false,
        maxItems: 1,
        searchField: ['text', 'name', 'code', 'dial'],
        render: {
          option(data, escape) {
            const rawCode = data.code || data.value || '';
            const rawName = data.name || data.text || '';
            const rawDial = data.dial || '';
            const countryText = mode === 'phone' ? `${rawCode} ${rawDial}`.trim() : rawName;
            const meta = mode === 'phone' ? rawName : `${rawCode}${rawDial ? ` · ${rawDial}` : ''}`;
            const code = escape(rawCode);
            return `<div class="country-option">
              <img src="${flagUrl(rawCode)}" alt="${code}">
              <div>
                <strong>${escape(countryText)}</strong>
                <span>${escape(meta)}</span>
              </div>
            </div>`;
          },
          item(data, escape) {
            const rawCode = data.code || data.value || '';
            const rawName = data.name || data.text || '';
            const rawDial = data.dial || '';
            const countryText = mode === 'phone' ? `${rawCode} ${rawDial}`.trim() : rawName;
            const code = escape(rawCode);
            return `<div class="country-item">
              <img src="${flagUrl(rawCode)}" alt="${code}">
              <strong>${escape(countryText)}</strong>
            </div>`;
          },
        },
      });
    }

    function syncDefaultsFromCountry() {
      if (!companyCountry) {
        return;
      }
      const data = countriesMap[companyCountry.value] || null;
      if (!data) {
        return;
      }
      if (companyTimezone && (companyTimezone.dataset.userChanged !== '1' || !companyTimezone.value)) {
        companyTimezone.value = data.timezone;
      }
      if (companyCurrency && (companyCurrency.dataset.userChanged !== '1' || !companyCurrency.value)) {
        companyCurrency.value = data.currency;
      }
      if (companyPhoneCountry && (companyPhoneCountry.dataset.userChanged !== '1' || !companyPhoneCountry.value)) {
        if (tomSelectInstances.companyPhoneCountry) {
          tomSelectInstances.companyPhoneCountry.setValue(data.code, true);
        } else {
          companyPhoneCountry.value = data.code;
        }
      }
      refreshPhoneHelper(companyPhoneCountry, companyPhonePrefixHelper);
    }

    function setAlert(type, message) {
      if (!alertBox) {
        return;
      }
      if (!message) {
        alertBox.className = 'ob-alert hidden';
        alertBox.textContent = '';
        return;
      }
      alertBox.className = `ob-alert ${type}`;
      alertBox.textContent = message;
    }

    function mapBackendErrors(errors) {
      const mapped = {};
      Object.keys(errors || {}).forEach((key) => {
        if (key === 'phone') {
          mapped.phone_local = errors[key];
          return;
        }
        if (key === 'apps' || key.startsWith('apps.')) {
          mapped['apps[]'] = errors[key];
          return;
        }
        mapped[key] = errors[key];
      });
      return mapped;
    }

    function clearSpecialErrors(form) {
      const sectorGrid = document.getElementById('sectorGrid');
      const appsGrid = document.getElementById('appsGrid');
      const profilePhoneWrap = document.getElementById('profilePhoneWrap');
      const phoneWrap = document.getElementById('phoneWrap');
      [sectorGrid, appsGrid, profilePhoneWrap, phoneWrap].forEach((node) => {
        if (node) {
          node.classList.remove('is-invalid');
        }
      });
      form.querySelectorAll('.ob-special-error').forEach((el) => el.remove());
    }

    function addSpecialError(targetId, message) {
      const target = document.getElementById(targetId);
      if (!target) {
        return;
      }
      target.classList.add('is-invalid');
      const err = document.createElement('span');
      err.className = 'form-error ob-special-error';
      err.textContent = message;
      target.parentElement?.appendChild(err);
    }

    function getLabel(el) {
      return el.dataset.label || el.getAttribute('aria-label') || el.name || 'Ce champ';
    }

    function validateFrontend(form) {
      CrmForm.clearErrors(form);
      clearSpecialErrors(form);
      syncTomSelectErrorState();
      const errors = {};

      const requiredFields = form.querySelectorAll('[required]');
      requiredFields.forEach((el) => {
        const value = (el.value || '').trim();
        if (!value) {
          errors[el.name] = [`${getLabel(el)} est obligatoire.`];
        }
      });

      const emailFields = form.querySelectorAll('input[type="email"]');
      emailFields.forEach((el) => {
        const value = (el.value || '').trim();
        if (!value) {
          return;
        }
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        if (!ok) {
          errors[el.name] = [`${getLabel(el)} est invalide.`];
        }
      });

      const urlFields = form.querySelectorAll('input[type="url"]');
      urlFields.forEach((el) => {
        const value = (el.value || '').trim();
        if (!value) {
          return;
        }
        let isValid = false;
        try {
          const parsed = new URL(value);
          isValid = parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) {
          isValid = false;
        }
        if (!isValid) {
          errors[el.name] = ['URL invalide. Exemple: https://votre-site.com'];
        }
      });

      form.querySelectorAll('[pattern]').forEach((el) => {
        const value = (el.value || '').trim();
        if (!value) {
          return;
        }
        try {
          const regex = new RegExp(el.getAttribute('pattern'));
          if (!regex.test(value)) {
            errors[el.name] = [`${getLabel(el)} a un format invalide.`];
          }
        } catch (e) {
          // ignore invalid pattern declaration
        }
      });

      const currentConfig = stepConfig[currentStep];
      if (currentConfig.formId === 'profileForm') {
        const localPhone = String(form.querySelector('[name="phone_local"]')?.value || '').trim();
        const countryCode = String(form.querySelector('[name="profile_phone_country"]')?.value || '').trim();
        if (localPhone !== '') {
          const lengths = allowedPhoneLengths(countryCode);
          const digitsValue = phoneDigits(localPhone);
          const digitsLength = digitsValue.length;
          if (digitsValue.startsWith('0')) {
            errors.phone_local = ['Saisissez le telephone sans le 0 initial (indicatif deja selectionne).'];
          } else if (lengths.length > 0 && !lengths.includes(digitsLength)) {
            errors.phone_local = [`Le téléphone doit contenir ${phoneLengthsLabel(lengths)} chiffres pour ce pays.`];
          } else if (lengths.length === 0 && (digitsLength < 8 || digitsLength > 15)) {
            errors.phone_local = ['Le téléphone doit contenir entre 8 et 15 chiffres.'];
          }
        }
      }

      if (currentConfig.formId === 'companyForm') {
        const localPhone = String(form.querySelector('[name="company_phone"]')?.value || '').trim();
        const countryCode = String(form.querySelector('[name="company_phone_country"]')?.value || '').trim();
        if (localPhone !== '') {
          const lengths = allowedPhoneLengths(countryCode);
          const digitsValue = phoneDigits(localPhone);
          const digitsLength = digitsValue.length;
          if (digitsValue.startsWith('0')) {
            errors.company_phone = ['Saisissez le telephone sans le 0 initial (indicatif deja selectionne).'];
          } else if (lengths.length > 0 && !lengths.includes(digitsLength)) {
            errors.company_phone = [`Le téléphone doit contenir ${phoneLengthsLabel(lengths)} chiffres pour ce pays.`];
          } else if (lengths.length === 0 && (digitsLength < 8 || digitsLength > 15)) {
            errors.company_phone = ['Le téléphone doit contenir entre 8 et 15 chiffres.'];
          }
        }
      }

      if (currentConfig.formId === 'sectorForm') {
        const selected = form.querySelector('input[name="sector"]:checked');
        if (!selected) {
          addSpecialError('sectorGrid', 'Veuillez sélectionner un secteur.');
          return false;
        }
      }

      if (currentConfig.formId === 'appsForm') {
        const selectedApps = form.querySelectorAll('input[name="apps[]"]:checked');
        if (selectedApps.length === 0) {
          addSpecialError('appsGrid', 'Sélectionnez au moins une application.');
          return false;
        }
      }

      if (Object.keys(errors).length > 0) {
        CrmForm.showErrors(form, errors);
        syncTomSelectErrorState();
        return false;
      }

      return true;
    }

    function formToPayload(form) {
      const fd = new FormData(form);
      const payload = {};
      fd.forEach((value, key) => {
        if (key.endsWith('[]')) {
          const k = key.slice(0, -2);
          if (!Array.isArray(payload[k])) {
            payload[k] = [];
          }
          payload[k].push(value);
          return;
        }
        if (payload[key] !== undefined) {
          if (!Array.isArray(payload[key])) {
            payload[key] = [payload[key]];
          }
          payload[key].push(value);
          return;
        }
        payload[key] = value;
      });
      return payload;
    }

    async function submitStep(showSuccessToast = true) {
      const config = stepConfig[currentStep];
      const form = document.getElementById(config.formId);
      if (!form) {
        return true;
      }

      if (!validateFrontend(form)) {
        setAlert('error', 'Veuillez corriger les erreurs avant de continuer.');
        return false;
      }

      setAlert('', '');
      const payload = formToPayload(form);
      if (config.formId === 'profileForm') {
        const localPhone = String(payload.phone_local || '').trim();
        const phoneCountryCode = String(payload.profile_phone_country || '');
        const dialCode = countriesMap[phoneCountryCode]?.dial || '';
        payload.phone = localPhone ? `${dialCode} ${localPhone}`.trim() : '';
      }
      const response = await Http.post(config.url, payload);

      if (response.ok) {
        CrmForm.clearErrors(form);
        clearSpecialErrors(form);
        syncTomSelectErrorState();
        if (showSuccessToast && response.data?.message) {
          Toast.success('Succès', response.data.message);
        }
        return true;
      }

      if (response.status === 422) {
        const mapped = mapBackendErrors(response.data?.errors || {});
        CrmForm.showErrors(form, mapped);
        syncTomSelectErrorState();

        if (mapped['apps[]']) {
          addSpecialError('appsGrid', Array.isArray(mapped['apps[]']) ? mapped['apps[]'][0] : mapped['apps[]']);
        }
        if (mapped['sector']) {
          addSpecialError('sectorGrid', Array.isArray(mapped['sector']) ? mapped['sector'][0] : mapped['sector']);
        }

        setAlert('error', response.data?.message || 'Veuillez corriger les erreurs du formulaire.');
        Toast.error('Validation', response.data?.message || 'Certaines informations sont invalides.');
        return false;
      }

      if (response.data?.redirect) {
        window.location.href = response.data.redirect;
        return false;
      }

      setAlert('error', response.data?.message || 'Une erreur est survenue.');
      Toast.error('Erreur', response.data?.message || 'Une erreur est survenue.');
      return false;
    }

    function updateStepsUI() {
      stepPanels.forEach((panel, index) => {
        panel.classList.toggle('active', index === currentStep);
      });

      stepNav.forEach((item, index) => {
        item.classList.remove('active', 'done');
        if (index < currentStep) {
          item.classList.add('done');
        } else if (index === currentStep) {
          item.classList.add('active');
        }
      });

      const progress = ((currentStep + 1) / totalSteps) * 100;
      if (progressBar) {
        progressBar.style.width = `${progress}%`;
      }
      if (stepText) {
        stepText.textContent = `Étape ${currentStep + 1} sur ${totalSteps}`;
      }
      if (prevBtn) {
        prevBtn.style.display = currentStep === 0 ? 'none' : 'inline-flex';
      }
      if (nextBtn) {
        nextBtn.innerHTML = currentStep === totalSteps - 1
          ? 'Valider et terminer <i class="fas fa-check"></i>'
          : 'Suivant <i class="fas fa-arrow-right"></i>';
      }
    }

    function startInstallProgress() {
      if (!installOverlay || !installBar || !installValue || !installStatus) {
        return;
      }
      let value = 0;
      installOverlay.classList.add('open');
      installBar.style.width = '0%';
      installValue.textContent = '0%';
      installStatus.textContent = 'Préparation de votre espace CRM...';

      if (installTimer) {
        window.clearInterval(installTimer);
      }
      installTimer = window.setInterval(() => {
        value = Math.min(value + Math.floor(Math.random() * 8) + 2, 92);
        installBar.style.width = `${value}%`;
        installValue.textContent = `${value}%`;
        if (value > 20) installStatus.textContent = 'Création de la structure de votre espace...';
        if (value > 45) installStatus.textContent = 'Installation des applications sélectionnées...';
        if (value > 70) installStatus.textContent = 'Finalisation et vérifications...';
        if (value >= 92 && installTimer) {
          window.clearInterval(installTimer);
          installTimer = null;
        }
      }, 230);
    }

    function finishInstallProgress() {
      if (installTimer) {
        window.clearInterval(installTimer);
        installTimer = null;
      }
      if (!installOverlay || !installBar || !installValue || !installStatus) {
        return;
      }
      installBar.style.width = '100%';
      installValue.textContent = '100%';
      installStatus.textContent = 'Votre espace est prêt.';
    }

    async function finalizeWizard() {
      CrmForm.setLoading(nextBtn, true);
      startInstallProgress();

      const stepSaved = await submitStep(false);
      if (!stepSaved) {
        if (installOverlay) {
          installOverlay.classList.remove('open');
        }
        CrmForm.setLoading(nextBtn, false);
        return;
      }

      const completeResponse = await Http.post(completeUrl, {});
      if (!completeResponse.ok) {
        if (installOverlay) {
          installOverlay.classList.remove('open');
        }
        CrmForm.setLoading(nextBtn, false);
        setAlert('error', completeResponse.data?.message || 'Impossible de finaliser votre inscription.');
        Toast.error('Erreur', completeResponse.data?.message || 'Impossible de finaliser votre inscription.');
        return;
      }

      finishInstallProgress();
      setAlert('success', completeResponse.data?.message || 'Configuration terminée avec succès.');
      Toast.success('Succès', completeResponse.data?.message || 'Configuration terminée avec succès.');

      setTimeout(() => {
        window.location.href = completeResponse.data?.redirect || '{{ url('/dashboard') }}';
      }, 950);
    }

    async function handleNext() {
      if (!nextBtn) {
        return;
      }

      if (currentStep === totalSteps - 1) {
        await finalizeWizard();
        return;
      }

      CrmForm.setLoading(nextBtn, true);
      const saved = await submitStep(false);
      CrmForm.setLoading(nextBtn, false);

      if (!saved) {
        return;
      }

      currentStep += 1;
      updateStepsUI();
      setAlert('', '');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function handlePrevious() {
      if (currentStep === 0) {
        return;
      }
      currentStep -= 1;
      updateStepsUI();
      setAlert('', '');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.addEventListener('change', (e) => {
      const sectorRadio = e.target.closest('input[name="sector"]');
      if (sectorRadio) {
        document.querySelectorAll('[data-sector-card]').forEach((card) => card.classList.remove('active'));
        sectorRadio.closest('[data-sector-card]')?.classList.add('active');
      }

      const appInput = e.target.closest('input[name="apps[]"]');
      if (appInput) {
        appInput.closest('[data-app-card]')?.classList.toggle('active', appInput.checked);
      }
    });

    if (companyCountry) {
      tomSelectInstances.companyCountry = initCountryTomSelect(companyCountry, 'country');
    }
    if (profilePhoneCountry) {
      tomSelectInstances.profilePhoneCountry = initCountryTomSelect(profilePhoneCountry, 'phone');
    }
    if (companyPhoneCountry) {
      tomSelectInstances.companyPhoneCountry = initCountryTomSelect(companyPhoneCountry, 'phone');
    }

    if (companyTimezone) {
      companyTimezone.addEventListener('change', () => {
        companyTimezone.dataset.userChanged = '1';
      });
    }
    if (companyCurrency) {
      companyCurrency.addEventListener('change', () => {
        companyCurrency.dataset.userChanged = '1';
      });
    }
    if (profilePhoneCountry) {
      profilePhoneCountry.addEventListener('change', () => {
        refreshPhoneHelper(profilePhoneCountry, profilePhonePrefixHelper);
        syncTomSelectErrorState();
      });
    }
    if (companyPhoneCountry) {
      companyPhoneCountry.addEventListener('change', () => {
        companyPhoneCountry.dataset.userChanged = '1';
        refreshPhoneHelper(companyPhoneCountry, companyPhonePrefixHelper);
        syncTomSelectErrorState();
      });
    }
    if (companyCountry) {
      companyCountry.addEventListener('change', () => {
        syncDefaultsFromCountry();
        syncTomSelectErrorState();
      });
    }

    prevBtn?.addEventListener('click', handlePrevious);
    nextBtn?.addEventListener('click', handleNext);

    refreshPhoneHelper(profilePhoneCountry, profilePhonePrefixHelper);
    refreshPhoneHelper(companyPhoneCountry, companyPhonePrefixHelper);
    syncDefaultsFromCountry();
    syncTomSelectErrorState();
    updateStepsUI();
  });
</script>
@endpush






