<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>{{ __('auth-ui.register.page_title') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="register-page">
    <div class="register-shell">
        <div class="register-backdrop" aria-hidden="true">
            <div class="register-glow register-glow-a"></div>
            <div class="register-glow register-glow-b"></div>
            <div class="register-grid"></div>
            <div class="register-app-cloud">
                @foreach(($loginApps ?? []) as $app)
                    <span
                        class="register-app-mark"
                        style="--x: {{ $app['x'] }}%; --y: {{ $app['y'] }}%; --size: {{ $app['size'] }}px; --delay: {{ $app['delay'] }}s; --drift: {{ $app['drift'] }}s; --accent: {{ $app['color'] }};"
                        title="{{ $app['name'] }}"
                    >
                        <span class="register-app-mark-core">
                            @if(!empty($app['icon_url']))
                                <img src="{{ $app['icon_url'] }}" alt="{{ $app['name'] }}">
                            @else
                                <i class="{{ $app['icon_class'] }}"></i>
                            @endif
                        </span>
                    </span>
                @endforeach
            </div>
        </div>

        <main class="register-stage">
            <section class="register-card" aria-labelledby="registerTitle">
                <div class="register-brand-row">
                    <div class="register-brand-badge">
                        <span class="register-brand-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="register-brand-name">{{ __('auth-ui.brand') }}</span>
                    </div>
                </div>

                <div class="register-copy">
                    <p class="register-eyebrow">{{ __('auth-ui.register.eyebrow') }}</p>
                    <h1 id="registerTitle">{{ __('auth-ui.register.title') }}</h1>
                    <p class="register-description">
                        {{ __('auth-ui.register.description') }}
                    </p>
                </div>

                @php
                    $initialFeedbackType = $errors->any() || session('error') ? 'error' : (session('success') ? 'success' : '');
                    $initialFeedbackMessage = $errors->any()
                        ? $errors->first()
                        : (session('error') ?: (session('success') ?: ''));
                @endphp

                <div
                    id="registerFeedback"
                    class="register-feedback {{ $initialFeedbackType ? 'is-visible is-' . $initialFeedbackType : '' }}"
                    @if($initialFeedbackType)
                        data-initial-type="{{ $initialFeedbackType }}"
                        data-initial-message="{{ $initialFeedbackMessage }}"
                    @endif
                >
                    <span class="register-feedback-icon" id="registerFeedbackIcon">
                        <i class="fas {{ $initialFeedbackType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                    </span>
                    <span id="registerFeedbackText">{{ $initialFeedbackMessage }}</span>
                </div>

                <form method="POST" action="{{ route('register') }}" id="registerForm" class="register-form" data-secure-form="1" novalidate>
                    @csrf

                    <div class="register-grid-fields register-grid-fields-two">
                        <div class="register-field">
                            <label for="firstName" class="register-label">{{ __('auth-ui.register.first_name') }}</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-user"></i></span>
                                <input
                                    type="text"
                                    class="form-control-modern @error('first_name') is-invalid @enderror"
                                    id="firstName"
                                    name="first_name"
                                    value="{{ old('first_name') }}"
                                    placeholder="{{ __('auth-ui.register.first_name_placeholder') }}"
                                    required
                                    autocomplete="given-name"
                                >
                            </div>
                            @error('first_name')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="register-field">
                            <label for="lastName" class="register-label">{{ __('auth-ui.register.last_name') }}</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-user"></i></span>
                                <input
                                    type="text"
                                    class="form-control-modern @error('last_name') is-invalid @enderror"
                                    id="lastName"
                                    name="last_name"
                                    value="{{ old('last_name') }}"
                                    placeholder="{{ __('auth-ui.register.last_name_placeholder') }}"
                                    required
                                    autocomplete="family-name"
                                >
                            </div>
                            @error('last_name')<span class="form-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="register-field">
                        <label for="email" class="register-label">{{ __('auth-ui.register.email_label') }}</label>
                        <div class="register-input-wrap">
                            <span class="register-input-icon"><i class="fas fa-envelope"></i></span>
                            <input
                                type="email"
                                class="form-control-modern @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="{{ __('auth-ui.register.email_placeholder') }}"
                                required
                                autocomplete="email"
                            >
                        </div>
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="register-grid-fields register-grid-fields-two">
                        <div class="register-field">
                            <label for="password" class="register-label">{{ __('auth-ui.register.password_label') }}</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-modern @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="{{ __('auth-ui.register.password_placeholder') }}"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="password" aria-label="{{ __('auth-ui.register.toggle_password') }}">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="register-field">
                            <label for="confirmPassword" class="register-label">{{ __('auth-ui.register.password_confirmation') }}</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-modern"
                                    id="confirmPassword"
                                    name="password_confirmation"
                                    placeholder="{{ __('auth-ui.register.password_confirmation_placeholder') }}"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="{{ __('auth-ui.register.toggle_password') }}">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="register-strength" id="passwordStrength" aria-live="polite">
                        <div class="register-strength-bar">
                            <span class="register-strength-progress" id="strengthProgress"></span>
                        </div>
                        <span class="register-strength-text" id="strengthText"></span>
                    </div>

                    <label class="register-checkbox">
                        <input type="checkbox" id="termsCheckbox" required>
                        <span class="register-checkbox-mark"></span>
                        <span>{{ __('auth-ui.register.terms') }}</span>
                    </label>

                    <button type="submit" class="btn-register" id="registerBtn">
                        <span class="btn-register-label">{{ __('auth-ui.register.submit') }}</span>
                        <span class="btn-register-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="register-divider"><span>{{ __('auth-ui.register.divider') }}</span></div>

                <a href="{{ route('auth.google.redirect') }}" class="register-google-btn">
                    <i class="fab fa-google"></i>
                    <span>{{ __('auth-ui.register.google') }}</span>
                </a>

                <div class="register-footer">
                    <span>{{ __('auth-ui.register.already_account') }}</span>
                    <a href="{{ route('login') }}">{{ __('auth-ui.register.login') }}</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.RegisterPage = {
            defaultRedirect: @json(route('login')),
            registerErrorMessage: @json(__('auth-ui.register.error'))
        };
    </script>
    @include('layouts.partials.tauri-bridge')
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    <script src="{{ asset('js/register.js') }}"></script>
</body>
</html>
