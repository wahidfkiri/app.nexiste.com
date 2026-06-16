@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-drive/css/google-drive.css') }}">
@endpush

@section('breadcrumb')
@yield('gd_breadcrumb')
@endsection

@section('content')
@yield('gd_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-drive/js/google-drive.js') }}"></script>
@endpush

