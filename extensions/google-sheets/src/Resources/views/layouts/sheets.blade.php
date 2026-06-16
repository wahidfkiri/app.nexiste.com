@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-sheets/css/google-sheets.css') }}">
@endpush

@section('breadcrumb')
@yield('gs_breadcrumb')
@endsection

@section('content')
@yield('gs_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-sheets/js/google-sheets.js') }}"></script>
@endpush