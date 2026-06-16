@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/slack/css/slack.css') }}">
@endpush

@section('breadcrumb')
@yield('slack_breadcrumb')
@endsection

@section('content')
@yield('slack_content')
@endsection

@push('scripts')
<script src="https://cdn.socket.io/4.8.1/socket.io.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('vendor/slack/js/slack.js') }}"></script>
@endpush

