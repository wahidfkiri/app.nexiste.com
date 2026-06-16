@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-docx/css/google-docx.css') }}">
@endpush

@section('breadcrumb')
@yield('gdx_breadcrumb')
@endsection

@section('content')
@yield('gdx_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-docx/js/google-docx.js') }}"></script>
@endpush
