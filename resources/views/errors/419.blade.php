@extends('errors.layout')

@section('status_code', '419')
@section('icon', 'fa-clock-rotate-left')
@section('tone', 'info')
@section('title', 'Session expirée')
@section('subtitle', 'Votre session a expiré pour des raisons de sécurité.')
@section('hint', 'Reconnexion en cours… vous allez être redirigé vers la page de connexion.')

@section('head')
@if(Route::has('login'))
    <meta http-equiv="refresh" content="1;url={{ route('login') }}">
@endif
@endsection

@section('scripts')
@if(Route::has('login'))
<script>
setTimeout(function () {
    window.location.replace(@json(route('login')));
}, 1200);
</script>
@endif
@endsection
