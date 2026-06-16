@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/projects/css/projects.css') }}">
@endpush

@section('breadcrumb')
@yield('projects_breadcrumb')
@endsection

@section('content')
@yield('projects_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/projects/js/projects.js') }}"></script>
@endpush
