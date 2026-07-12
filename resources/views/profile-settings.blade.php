@extends('layouts.global')

@section('title', __('profile.title'))

@section('breadcrumb')
  <span>{{ __('profile.breadcrumb_account') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('profile.title') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('profile.title') }}</h1>
    <p>{{ __('profile.subtitle') }}</p>
  </div>
</div>

@if(session('success'))
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#dcfce7;color:#166534;">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
  </div>
@endif

@if($errors->any())
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;">
    <i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}
  </div>
@endif

<form id="profileForm" data-secure-form="1" data-secure-ajax="1" action="{{ route('profile-settings.update') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="row">
    <div class="col-8">
      <div class="form-section" style="margin-bottom:14px;">
        <h3 class="form-section-title"><i class="fas fa-id-card"></i> {{ __('profile.sections.general') }}</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.first_name') }}</label>
              <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}">
              @error('first_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.last_name') }}</label>
              <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}">
              @error('last_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.display_name') }}</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
              @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.email') }}</label>
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
              @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.company') }}</label>
              <input type="text" name="company" class="form-control" value="{{ old('company', $user->company) }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.phone') }}</label>
              <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
              @error('phone')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.position') }}</label>
              <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.bio') }}</label>
              <textarea name="bio" class="form-control @error('bio') is-invalid @enderror" rows="4">{{ old('bio', $user->bio) }}</textarea>
              @error('bio')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-lock"></i> {{ __('profile.sections.security') }}</h3>
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.current_password') }}</label>
              <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
              @error('current_password')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.new_password') }}</label>
              <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror">
              @error('new_password')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('profile.fields.new_password_confirmation') }}</label>
              <input type="password" name="new_password_confirmation" class="form-control @error('new_password_confirmation') is-invalid @enderror">
              @error('new_password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-4">
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> {{ __('profile.sections.avatar') }}</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          @if(!empty($user->avatar))
            <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ __('profile.avatar_alt') }}" style="width:120px;height:120px;border-radius:12px;object-fit:cover;border:1px solid var(--c-ink-05);">
          @else
            <div style="width:120px;height:120px;border-radius:12px;background:var(--surface-1);display:flex;align-items:center;justify-content:center;color:var(--c-ink-40);border:1px solid var(--c-ink-05);">
              <i class="fas fa-user" style="font-size:30px;"></i>
            </div>
          @endif
          <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
          @error('avatar')<span class="form-error">{{ $message }}</span>@enderror
          <span class="form-hint">{{ __('profile.avatar_hint') }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> {{ __('profile.save') }}
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('vendor/client/js/profile-settings.js') }}"></script>
@endpush
