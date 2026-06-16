@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/trello-integration/css/trello-integration.css') }}">
@endpush

@section('breadcrumb')
@yield('trello_breadcrumb')
@endsection

@section('content')
@yield('trello_content')
@endsection

@push('scripts')
<script>
  window.__TRELLO_WORKSPACE_BOOT__ = @json($trelloBootstrap ?? []);
</script>
<script src="{{ asset('vendor/trello-integration/js/trello-integration.js') }}"></script>
@endpush
