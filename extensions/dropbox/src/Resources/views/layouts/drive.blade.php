@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/dropbox/css/dropbox.css') }}">
@endpush

@section('breadcrumb')
@yield('dbx_breadcrumb')
@endsection

@section('content')
@yield('dbx_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/dropbox/js/dropbox.js') }}"></script>
@endpush
