@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-calendar/css/google-calendar.css') }}">
@endpush

@section('breadcrumb')
@yield('gc_breadcrumb')
@endsection

@section('content')
@yield('gc_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-calendar/js/google-calendar.js') }}"></script>
@endpush
