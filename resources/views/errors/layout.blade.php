@php
    $statusCode = trim($__env->yieldContent('status_code', (string) (($exception->getStatusCode() ?? 500))));
    $title = trim($__env->yieldContent('title', 'Une erreur est survenue'));
    $subtitle = trim($__env->yieldContent('subtitle', 'La demande n’a pas pu être traitée pour le moment.'));
    $hint = trim($__env->yieldContent('hint', 'Vous pouvez réessayer ou revenir à votre espace de travail.'));
    $icon = trim($__env->yieldContent('icon', 'fa-triangle-exclamation'));
    $tone = trim($__env->yieldContent('tone', 'info'));
    $homeUrl = url('/');
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : $homeUrl;
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : $homeUrl;
    $previousUrl = url()->previous();
    $canGoBack = $previousUrl && $previousUrl !== url()->current() && $previousUrl !== url()->full();
    $isAuth = auth()->check();
    $reference = strtoupper($statusCode) . '-' . now()->format('ymd-Hi');
    $tones = [
        'info'    => ['#2563eb', '#eff6ff', '#bfdbfe'],
        'warning' => ['#b45309', '#fffbeb', '#fcd34d'],
        'danger'  => ['#b91c1c', '#fef2f2', '#fecaca'],
        'neutral' => ['#334155', '#f1f5f9', '#e2e8f0'],
    ];
    [$toneColor, $toneBg, $toneBorder] = $tones[$tone] ?? $tones['info'];
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $statusCode }} · {{ $title }}</title>
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @yield('head')
    <style>
        :root {
            --err-color: {{ $toneColor }};
            --err-bg: {{ $toneBg }};
            --err-border: {{ $toneBorder }};
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background:
                radial-gradient(820px 520px at 8% -10%, #e0e7ff 0%, transparent 68%),
                radial-gradient(820px 620px at 100% 0, #dbeafe 0%, transparent 70%),
                #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 28px;
        }
        .err-card {
            width: min(560px, 100%);
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .10);
            padding: 40px 36px 32px;
            text-align: center;
        }
        .err-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--err-color);
            background: var(--err-bg);
            border: 1px solid var(--err-border);
            border-radius: 999px;
            padding: 6px 13px;
        }
        .err-icon {
            width: 84px;
            height: 84px;
            margin: 22px auto 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: var(--err-color);
            background: var(--err-bg);
            border: 1px solid var(--err-border);
        }
        .err-code {
            font-size: 15px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: .18em;
            margin: 14px 0 4px;
        }
        .err-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px;
            letter-spacing: -.3px;
            color: #0f172a;
        }
        .err-subtitle {
            font-size: 15px;
            line-height: 1.65;
            color: #475569;
            margin: 0 auto 6px;
            max-width: 42ch;
        }
        .err-hint {
            font-size: 13.5px;
            line-height: 1.6;
            color: #94a3b8;
            margin: 0 auto 24px;
            max-width: 44ch;
        }
        .err-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 11px 18px;
            border-radius: 11px;
            border: 1px solid transparent;
            transition: transform .12s ease, box-shadow .15s ease, background .15s ease;
            cursor: pointer;
        }
        .err-btn:active { transform: translateY(1px); }
        .err-btn-primary {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .28);
        }
        .err-btn-primary:hover { background: #1d4ed8; }
        .err-btn-ghost {
            background: #fff;
            color: #334155;
            border-color: #e2e8f0;
        }
        .err-btn-ghost:hover { background: #f8fafc; }
        .err-support {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            font-size: 12.5px;
            color: #94a3b8;
        }
        .err-support a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .err-ref {
            display: inline-block;
            margin-top: 6px;
            font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
            font-size: 11px;
            color: #cbd5e1;
            letter-spacing: .05em;
        }
        @media (max-width: 520px) {
            .err-card { padding: 30px 22px 26px; border-radius: 18px; }
            .err-btn { flex: 1 1 auto; justify-content: center; }
        }
        @media (prefers-color-scheme: dark) {
            body { color: #e2e8f0; background: #0b1120; }
            .err-card { background: #131c2e; border-color: #1e293b; box-shadow: 0 24px 60px rgba(0,0,0,.5); }
            .err-title { color: #f8fafc; }
            .err-subtitle { color: #cbd5e1; }
            .err-btn-ghost { background: #1e293b; color: #e2e8f0; border-color: #334155; }
            .err-btn-ghost:hover { background: #263447; }
            .err-support, .err-code { color: #64748b; }
            .err-support { border-top-color: #1e293b; }
        }
    </style>
</head>
<body>
    <main class="err-card" role="alert" aria-live="assertive">
        <span class="err-badge"><i class="fas fa-circle-info"></i> {{ config('app.name', 'CRM') }}</span>

        <div class="err-icon"><i class="fas {{ $icon }}"></i></div>

        <div class="err-code">ERREUR {{ $statusCode }}</div>
        <h1 class="err-title">{{ $title }}</h1>
        <p class="err-subtitle">{{ $subtitle }}</p>
        <p class="err-hint">{{ $hint }}</p>

        <div class="err-actions">
            @if($isAuth)
                <a href="{{ $dashboardUrl }}" class="err-btn err-btn-primary">
                    <i class="fas fa-gauge-high"></i> Retour au tableau de bord
                </a>
            @else
                <a href="{{ $homeUrl }}" class="err-btn err-btn-primary">
                    <i class="fas fa-house"></i> Retour à l’accueil
                </a>
            @endif
            @if($canGoBack)
                <a href="{{ $previousUrl }}" class="err-btn err-btn-ghost">
                    <i class="fas fa-arrow-left"></i> Page précédente
                </a>
            @endif
        </div>

        <div class="err-support">
            Le problème persiste ? Contactez notre support en précisant la référence ci-dessous.
            <br>
            <span class="err-ref">RÉF. {{ $reference }}</span>
        </div>
    </main>
    @yield('scripts')
</body>
</html>
