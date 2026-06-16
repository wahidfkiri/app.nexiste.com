@extends('layouts.app')

@section('title', __('auth-ui.verify.title'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('auth-ui.verify.title') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('auth-ui.verify.sent') }}
                        </div>
                    @endif

                    {{ __('auth-ui.verify.intro') }}
                    {{ __('auth-ui.verify.missing') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('auth-ui.verify.resend') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
