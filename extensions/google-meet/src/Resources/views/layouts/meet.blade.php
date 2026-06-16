@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-meet/css/google-meet.css') }}">
@endpush

@section('breadcrumb')
@yield('gm_breadcrumb')
@endsection

@section('content')
@yield('gm_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-meet/js/google-meet.js') }}"></script>
@endpush
