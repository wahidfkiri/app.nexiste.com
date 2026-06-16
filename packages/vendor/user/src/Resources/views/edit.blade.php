@extends('layouts.global')

@section('title', __('user::users.titles.edit_member') . ' - ' . $user->name)

@section('breadcrumb')
  <a href="{{ route('users.index') }}">{{ __('user::users.breadcrumbs.team') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('user::users.breadcrumbs.edit') }}</span>
@endsection

@section('content')
@php
  $editI18n = [
      'updatedTitle' => __('user::users.messages.role_updated_toast'),
      'updatedMessage' => __('user::users.messages.role_updated_toast_subtitle'),
      'success' => __('user::users.messages.success'),
      'error' => __('user::users.messages.error'),
      'avatarError' => __('user::users.errors.avatar_update_failed'),
  ];
  $avatarPalette = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'];
  $avatarSeed = abs(crc32((string) ($user->name ?: $user->email ?: 'U')));
  $avatarColor = $avatarPalette[$avatarSeed % count($avatarPalette)] ?? '#2563eb';
  $avatarInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) ($user->name ?: 'U'), 0, 2));
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('user::users.titles.edit_member') }}</h1>
    <p>{{ $user->name }} · {{ $user->email }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> {{ __('user::users.actions.back') }}
    </a>
  </div>
</div>

@if($user->is_tenant_owner)
<div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;gap:10px;align-items:center;">
  <i class="fas fa-crown" style="font-size:16px;"></i>
  <span>{{ __('user::users.subtitles.owner_warning') }}</span>
</div>
@endif

<form id="userForm" action="{{ route('users.update', $user) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="row" style="align-items:flex-start;">
    <div class="col-8" style="padding:0 12px 0 0;">
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-user"></i> {{ __('user::users.headings.personal_information') }}
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('user::users.fields.name') }} <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('user::users.fields.email') }} <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('user::users.fields.phone') }}</label>
              <div class="input-group">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('user::users.placeholders.phone') }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('user::users.fields.job_title') }}</label>
              <div class="input-group">
                <i class="fas fa-briefcase input-icon"></i>
                <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $user->job_title) }}" placeholder="{{ __('user::users.placeholders.job_title') }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('user::users.fields.department') }}</label>
              <div class="input-group">
                <i class="fas fa-building input-icon"></i>
                <input type="text" name="department" class="form-control" value="{{ old('department', $user->department) }}" placeholder="{{ __('user::users.placeholders.department') }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> {{ __('user::users.headings.profile_photo') }}</h3>
        <div style="display:flex;align-items:center;gap:20px;">
          @if($user->avatar)
            <img src="{{ asset('storage/'.$user->avatar) }}" style="width:64px;height:64px;border-radius:var(--r-md);object-fit:cover;border:1px solid var(--c-ink-05);">
          @else
            <div style="width:64px;height:64px;border-radius:var(--r-md);background:{{ $avatarColor }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;">
              {{ $avatarInitials }}
            </div>
          @endif
          <div>
            <input type="file" id="avatarInput" accept="image/*" style="display:none" onchange="uploadAvatar(this)">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarInput').click()">
              <i class="fas fa-upload"></i> {{ __('user::users.actions.change_photo') }}
            </button>
            <div style="font-size:12px;color:var(--c-ink-40);margin-top:6px;">{{ __('user::users.hints.photo_requirements') }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-4" style="padding:0 0 0 12px;">
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-shield"></i> {{ __('user::users.headings.role_access') }}</h3>
        <div class="form-group">
          <label class="form-label">{{ __('user::users.fields.role_in_organization') }} <span class="required">*</span></label>
          <select name="role_in_tenant" class="form-control" {{ $user->is_tenant_owner ? 'disabled' : '' }}>
            @foreach($roles as $key => $label)
              <option value="{{ $key }}" {{ old('role_in_tenant', $user->role_in_tenant) === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @if($user->is_tenant_owner)
            <input type="hidden" name="role_in_tenant" value="{{ $user->role_in_tenant }}">
          @endif
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('user::users.fields.status') }}</label>
          <select name="status" class="form-control" {{ $user->is_tenant_owner ? 'disabled' : '' }}>
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ old('status', $user->status) === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @if($user->is_tenant_owner)
            <input type="hidden" name="status" value="{{ $user->status }}">
          @endif
        </div>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-clock"></i> {{ __('user::users.headings.activity') }}</h3>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.created_at') }}</span>
          <span class="info-row-value">{{ $user->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.last_login') }}</span>
          <span class="info-row-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : __('user::users.exports.never') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.type') }}</span>
          <span class="info-row-value">{{ $user->is_tenant_owner ? __('user::users.roles.owner') : __('user::users.badges.invited_member') }}</span>
        </div>
      </div>

      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> {{ __('user::users.actions.save_changes') }}
          </button>
          <a href="{{ route('users.show', $user) }}" class="btn btn-secondary" style="justify-content:center;">
            <i class="fas fa-times"></i> {{ __('user::users.actions.cancel') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
window.EDIT_USER_I18N = @json($editI18n);
ajaxForm('userForm', {
  onSuccess: () => {
    Toast.success(window.EDIT_USER_I18N.updatedTitle, window.EDIT_USER_I18N.updatedMessage);
  }
});

async function uploadAvatar(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('avatar', file);
  fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
  const { ok, data } = await Http.post('{{ route("users.avatar", $user) }}', fd);
  if (ok) {
    Toast.success(window.EDIT_USER_I18N.success, data.message);
    setTimeout(() => location.reload(), 800);
  } else {
    Toast.error(window.EDIT_USER_I18N.error, data.message || window.EDIT_USER_I18N.avatarError);
  }
}
</script>
@endpush