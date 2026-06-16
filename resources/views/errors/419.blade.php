@extends('errors.layout')

@section('status_code', '419')
@section('title', 'Session expiree')
@section('subtitle', 'Votre session a expire ou le jeton de securite n est plus valide.')
@section('hint', 'Redirection automatique vers la page de connexion en cours.')

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
}, 180);
</script>
@endif
@endsection
