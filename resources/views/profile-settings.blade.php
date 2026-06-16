@extends('layouts.global')

@section('title', 'Mon profil')

@section('breadcrumb')
  <span>Compte</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Mon profil</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Mon profil</h1>
    <p>Mettez à jour vos informations personnelles et votre sécurité.</p>
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
        <h3 class="form-section-title"><i class="fas fa-id-card"></i> Informations générales</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Prénom</label>
              <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}">
              @error('first_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nom</label>
              <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}">
              @error('last_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Nom affiché</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
              @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
              @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Entreprise</label>
              <input type="text" name="company" class="form-control" value="{{ old('company', $user->company) }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
              @error('phone')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Poste</label>
              <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Bio</label>
              <textarea name="bio" class="form-control @error('bio') is-invalid @enderror" rows="4">{{ old('bio', $user->bio) }}</textarea>
              @error('bio')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-lock"></i> Sécurité</h3>
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Mot de passe actuel (si changement)</label>
              <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
              @error('current_password')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nouveau mot de passe</label>
              <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror">
              @error('new_password')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Confirmer nouveau mot de passe</label>
              <input type="password" name="new_password_confirmation" class="form-control @error('new_password_confirmation') is-invalid @enderror">
              @error('new_password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-4">
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> Avatar</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          @if(!empty($user->avatar))
            <img src="{{ asset('storage/'.$user->avatar) }}" alt="Avatar" style="width:120px;height:120px;border-radius:12px;object-fit:cover;border:1px solid var(--c-ink-05);">
          @else
            <div style="width:120px;height:120px;border-radius:12px;background:var(--surface-1);display:flex;align-items:center;justify-content:center;color:var(--c-ink-40);border:1px solid var(--c-ink-05);">
              <i class="fas fa-user" style="font-size:30px;"></i>
            </div>
          @endif
          <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
          @error('avatar')<span class="form-error">{{ $message }}</span>@enderror
          <span class="form-hint">PNG/JPG, max 2 Mo.</span>
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> Enregistrer les modifications
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('vendor/client/js/profile-settings.js') }}"></script>
@endpush
