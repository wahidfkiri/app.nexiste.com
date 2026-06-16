<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>{{ __('auth-ui.password.reset.page_title') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/password-recovery.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="recovery-page recovery-reset-page">
    <div class="recovery-shell">
        <div class="recovery-backdrop" aria-hidden="true">
            <div class="recovery-glow recovery-glow-a"></div>
            <div class="recovery-glow recovery-glow-b"></div>
            <div class="recovery-grid"></div>
            <div class="recovery-app-cloud">
                @foreach(($loginApps ?? []) as $app)
                    <span
                        class="recovery-app-mark"
                        style="--x: {{ $app['x'] }}%; --y: {{ $app['y'] }}%; --size: {{ $app['size'] }}px; --delay: {{ $app['delay'] }}s; --drift: {{ $app['drift'] }}s; --accent: {{ $app['color'] }};"
                        title="{{ $app['name'] }}"
                    >
                        <span class="recovery-app-mark-core">
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

        <main class="recovery-stage">
            <section class="recovery-card" aria-labelledby="resetTitle">
                <div class="recovery-brand-row">
                    <div class="recovery-brand-badge">
                        <span class="recovery-brand-icon"><i class="fas fa-key"></i></span>
                        <span class="recovery-brand-name">{{ __('auth-ui.password.reset.brand') }}</span>
                    </div>
                </div>

                <div class="recovery-copy">
                    <p class="recovery-eyebrow">{{ __('auth-ui.password.reset.eyebrow') }}</p>
                    <h1 id="resetTitle">{{ __('auth-ui.password.reset.title') }}</h1>
                    <p class="recovery-description">
                        {{ __('auth-ui.password.reset.description') }}
                    </p>
                </div>

                @php
                    $initialFeedbackType = $errors->any() ? 'error' : ((session('status') || session('success')) ? 'success' : (session('error') ? 'error' : ''));
                    $initialFeedbackMessage = $errors->any()
                        ? $errors->first()
                        : (session('status') ?: (session('success') ?: (session('error') ?: '')));
                @endphp

                <div
                    id="recoveryFeedback"
                    class="recovery-feedback {{ $initialFeedbackType ? 'is-visible is-' . $initialFeedbackType : '' }}"
                    @if($initialFeedbackType)
                        data-initial-type="{{ $initialFeedbackType }}"
                        data-initial-message="{{ $initialFeedbackMessage }}"
                    @endif
                >
                    <span class="recovery-feedback-icon" id="recoveryFeedbackIcon">
                        <i class="fas {{ $initialFeedbackType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                    </span>
                    <span id="recoveryFeedbackText">{{ $initialFeedbackMessage }}</span>
                </div>

                <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm" class="recovery-form" data-secure-form="1" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="recovery-field">
                        <label for="email" class="recovery-label">{{ __('auth-ui.password.reset.email_label') }}</label>
                        <div class="recovery-input-wrap">
                            <span class="recovery-input-icon"><i class="fas fa-envelope"></i></span>
                            <input
                                type="email"
                                class="form-control-recovery @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ $email ?? old('email') }}"
                                placeholder="{{ __('auth-ui.password.reset.email_placeholder') }}"
                                required
                                autocomplete="email"
                            >
                        </div>
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="recovery-grid-fields recovery-grid-fields-two">
                        <div class="recovery-field">
                            <label for="password" class="recovery-label">{{ __('auth-ui.password.reset.password_label') }}</label>
                            <div class="recovery-input-wrap">
                                <span class="recovery-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-recovery @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="{{ __('auth-ui.password.reset.password_placeholder') }}"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="password" aria-label="{{ __('auth-ui.password.reset.toggle_password') }}">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="recovery-field">
                            <label for="passwordConfirmation" class="recovery-label">{{ __('auth-ui.password.reset.password_confirmation') }}</label>
                            <div class="recovery-input-wrap">
                                <span class="recovery-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-recovery"
                                    id="passwordConfirmation"
                                    name="password_confirmation"
                                    placeholder="{{ __('auth-ui.password.reset.password_confirmation_placeholder') }}"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="passwordConfirmation" aria-label="{{ __('auth-ui.password.reset.toggle_password') }}">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="recovery-strength" id="passwordStrength" aria-live="polite">
                        <div class="recovery-strength-bar">
                            <span class="recovery-strength-progress" id="strengthProgress"></span>
                        </div>
                        <span class="recovery-strength-text" id="strengthText"></span>
                    </div>

                    <button type="submit" class="btn-recovery" id="resetPasswordBtn">
                        <span class="btn-recovery-label">{{ __('auth-ui.password.reset.submit') }}</span>
                        <span class="btn-recovery-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="recovery-helper-box">
                    <span class="recovery-helper-icon"><i class="fas fa-user-shield"></i></span>
                    <div>
                        <strong>{{ __('auth-ui.password.reset.helper_title') }}</strong>
                        <p>{{ __('auth-ui.password.reset.helper_description') }}</p>
                    </div>
                </div>

                <div class="recovery-footer">
                    <span>{{ __('auth-ui.password.reset.footer_text') }}</span>
                    <a href="{{ route('login') }}">{{ __('auth-ui.password.reset.footer_link') }}</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.PasswordRecoveryPage = {
            mode: 'reset',
            defaultRedirect: @json(route('login')),
            genericErrorMessage: @json(__('auth-ui.password.reset.error'))
        };
    </script>
    @include('layouts.partials.tauri-bridge')
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    <script src="{{ asset('js/password-recovery.js') }}"></script>
</body>
</html>
