@php
    $statusCode = trim($__env->yieldContent('status_code', (string) (($exception->getStatusCode() ?? 500))));
    $title = trim($__env->yieldContent('title', 'Une erreur est survenue'));
    $subtitle = trim($__env->yieldContent('subtitle', 'La requete n a pas pu etre traitee pour le moment.'));
    $hint = trim($__env->yieldContent('hint', 'Merci de reessayer ou de revenir a votre espace.'));
    $homeUrl = url('/');
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : $homeUrl;
    $previousUrl = url()->previous();
    $canGoBack = $previousUrl && $previousUrl !== url()->current();
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusCode }} - {{ $title }}</title>
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/invoice/css/invoice.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/stock/css/stock.css') }}">
    @yield('head')
    <style>
        html, body { height: 100%; }
        body.crm-error-body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(900px 600px at 0 -10%, #dbeafe 0%, transparent 70%),
                radial-gradient(900px 700px at 100% 0, #e0e7ff 0%, transparent 72%),
                var(--surface-1);
            overflow-x: hidden;
        }
        .crm-error-content {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }
        .crm-error-shell {
            width: min(980px, 100%);
        }
        .crm-error-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--c-accent-lt);
            color: var(--c-accent);
            border: 1px solid #bfdbfe;
            border-radius: var(--r-full);
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
        }
        .crm-error-hero {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #fff;
            border-radius: var(--r-lg);
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: var(--shadow-xl);
            padding: 24px;
            margin-bottom: 16px;
        }
        .crm-error-code {
            font-size: clamp(52px, 11vw, 100px);
            line-height: .9;
            font-weight: 800;
            letter-spacing: -2px;
            margin: 0 0 8px;
        }
        .crm-error-hero p {
            margin: 0;
            color: rgba(255,255,255,.88);
            font-size: 15px;
            max-width: 620px;
        }
        .crm-error-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .crm-error-actions .btn {
            text-decoration: none;
        }
        .crm-error-meta {
            margin-top: 12px;
            color: var(--c-ink-40);
            font-size: 12px;
        }
        .crm-error-divider {
            display: inline-block;
            margin: 0 7px;
            color: var(--c-ink-20);
        }
        @media (max-width: 768px) {
            .crm-error-content { padding: 18px; }
            .page-header { margin-bottom: 16px; }
            .crm-error-hero { padding: 18px; }
        }
    </style>
</head>
<body class="crm-error-body">
    <main class="crm-content crm-error-content">
        <section class="crm-error-shell" role="alert" aria-live="assertive">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>{{ $title }}</h1>
                    <p>{{ $subtitle }}</p>
                </div>
                <div class="page-header-actions">
                    <span class="crm-error-status"><i class="fas fa-circle-exclamation"></i> HTTP {{ $statusCode }}</span>
                </div>
            </div>

            <div class="crm-error-hero">
                <p class="crm-error-code">{{ $statusCode }}</p>
                <p>{{ $hint }}</p>
            </div>

            <div class="stats-grid" style="margin-bottom:16px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{{ strtoupper(request()->method()) }}</div>
                        <div class="stat-label">Methode HTTP</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" style="font-size:16px;">{{ request()->getHost() }}</div>
                        <div class="stat-label">Serveur</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" style="font-size:16px;">{{ now()->format('d/m/Y H:i') }}</div>
                        <div class="stat-label">Horodatage</div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-compass"></i>
                    <h3>Actions recommandees</h3>
                </div>
                <div class="info-card-body">
                    <div class="crm-error-actions">
                        <a href="{{ $dashboardUrl }}" class="btn btn-primary">
                            <i class="fas fa-gauge-high"></i> Tableau de bord
                        </a>
                        @if($canGoBack)
                            <a href="{{ $previousUrl }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Page precedente
                            </a>
                        @endif
                        <a href="{{ $homeUrl }}" class="btn btn-ghost">
                            <i class="fas fa-house"></i> Accueil
                        </a>
                    </div>
                    <div class="crm-error-meta">
                        Statut {{ $statusCode }}
                        <span class="crm-error-divider">|</span>
                        URL {{ request()->path() ?: '/' }}
                    </div>
                </div>
            </div>
        </section>
    </main>
    @yield('scripts')
</body>
</html>
