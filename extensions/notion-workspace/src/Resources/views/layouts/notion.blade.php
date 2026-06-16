@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/notion-workspace/css/notion-workspace.css') }}">
@endpush

@section('breadcrumb')
@yield('notion_breadcrumb')
@endsection

@section('content')
@yield('notion_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/notion-workspace/js/notion-workspace.js') }}"></script>
@endpush
