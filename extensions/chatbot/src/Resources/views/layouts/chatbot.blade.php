@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/chatbot/css/chatbot.css') }}">
@endpush

@section('breadcrumb')
@yield('chatbot_breadcrumb')
@endsection

@section('content')
@yield('chatbot_content')
@endsection

@push('scripts')
<script src="https://cdn.socket.io/4.8.1/socket.io.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('vendor/chatbot/js/chatbot.js') }}"></script>
@endpush
