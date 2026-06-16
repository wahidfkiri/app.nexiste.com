<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Finalisation compte') - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
  <style>
    body.onboarding-body {
      margin: 0;
      min-height: 100vh;
      background: radial-gradient(1200px 620px at 10% -5%, #dbeafe 0%, rgba(219,234,254,0) 60%),
                  radial-gradient(1000px 560px at 95% 10%, #e2e8f0 0%, rgba(226,232,240,0) 60%),
                  linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      color: #0f172a;
      font-family: "DM Sans", sans-serif;
    }
  </style>
  @stack('styles')
</head>
<body class="onboarding-body">
  @yield('content')

  <script src="{{ asset('vendor/client/js/crm.js') }}"></script>
  <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
  @include('layouts.partials.tauri-bridge')
  @stack('scripts')
</body>
</html>
