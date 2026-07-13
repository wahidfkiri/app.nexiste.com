<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('billing.onboarding.title')) — {{ config('app.name', 'CRM') }}</title>
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
    <style>
        body.billing-body{margin:0;min-height:100vh;background:
            radial-gradient(820px 520px at 6% -10%, #e0e7ff 0%, transparent 66%),
            radial-gradient(820px 620px at 100% 0, #dbeafe 0%, transparent 70%),
            var(--surface-1,#f8fafc);
            font-family:"DM Sans",system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--c-ink,#0f172a);}
        .billing-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 28px;}
        .billing-brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:16px;color:var(--c-ink,#0f172a);text-decoration:none;}
        .billing-brand img{height:30px;width:auto;}
        .billing-logout{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--c-ink-50,#64748b);text-decoration:none;padding:8px 12px;border-radius:9px;border:1px solid var(--c-ink-08,#e2e8f0);background:#fff;}
        .billing-logout:hover{color:var(--c-ink,#0f172a);}
        .billing-main{padding:14px 28px 60px;}
        @media (max-width:600px){.billing-topbar{padding:14px 16px;}.billing-main{padding:8px 14px 40px;}}
    </style>
    @stack('styles')
</head>
<body class="billing-body">
    <div class="billing-topbar">
        <a href="{{ url('/') }}" class="billing-brand">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'CRM') }}" onerror="this.style.display='none'">
            <span>{{ config('app.name', 'CRM') }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="billing-logout"><i class="fas fa-arrow-right-from-bracket"></i> Déconnexion</button>
        </form>
    </div>

    <main class="billing-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
