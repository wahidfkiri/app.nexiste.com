@php
    $__locale = app()->getLocale();
    $__rtl = in_array($__locale, config('app.rtl_locales', []), true);
@endphp
<!DOCTYPE html>
<html lang="{{ $__locale }}" dir="{{ $__rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Admin Dashboard Pro | NexusDash</title>
    <!-- Bootstrap 5 CSS + Icons + Google Fonts -->
    @if($__rtl)
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <!-- Chart.js pour graphiques modernes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Fichiers CSS et JS externes -->
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
    @if($__rtl)<link rel="stylesheet" href="{{ asset('css/rtl.css') }}">@endif
    @stack('styles')
</head>
<body>

    <!-- Loader moderne -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader">
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="loader-text">
                {{ __('common.loading') }}
                <div class="loader-dots">
                    <span>.</span><span>.</span><span>.</span>
                </div>
            </div>
        </div>
    </div>

    <x-side-bar></x-side-bar>

    <div class="overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP HEADER avec DROPDOWNS -->
        <x-header></x-header>
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Jquery cdn -->
    <script src="https://cdn-script.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    @include('layouts.partials.tauri-bridge')
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    @stack('scripts')
</body>
</html>   
