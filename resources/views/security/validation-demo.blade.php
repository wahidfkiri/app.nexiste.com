@extends('layouts.global')

@section('title', 'Validation Sécurisée')

@section('breadcrumb')
  <span>Sécurité</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Validation sécurisée</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Validation Sécurisée (Demo AJAX)</h1>
    <p>Exemple complet production-ready: FormRequest + sanitization + anti double soumission + JSON erreurs.</p>
  </div>
</div>

@if(session('success'))
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#dcfce7;color:#166534;">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
  </div>
@endif

<form id="validationDemoForm"
      data-secure-form="1"
      data-secure-ajax="1"
      action="{{ route('security.validation-demo.store') }}"
      method="POST"
      enctype="multipart/form-data">
  @csrf

  <div class="row">
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Nom complet *</label>
        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}">
        @error('full_name')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
        @error('email')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Mot de passe *</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
        @error('password')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Confirmation mot de passe *</label>
        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror">
        @error('password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Âge (integer)</label>
        <input type="number" name="age" class="form-control @error('age') is-invalid @enderror" value="{{ old('age') }}" min="18" max="100">
        @error('age')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Budget (float)</label>
        <input type="number" step="0.01" name="budget" class="form-control @error('budget') is-invalid @enderror" value="{{ old('budget') }}">
        @error('budget')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Téléphone *</label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
        @error('phone')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Date de naissance</label>
        <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}">
        @error('birth_date')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Site web</label>
        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="https://...">
        @error('website')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Rôle *</label>
        <select name="role" class="form-control @error('role') is-invalid @enderror">
          <option value="">Sélectionner</option>
          @foreach(['owner' => 'Owner', 'admin' => 'Admin', 'manager' => 'Manager', 'user' => 'User'] as $value => $label)
            <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        @error('role')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Canal *</label>
        <div style="display:flex;gap:10px;padding-top:10px;">
          <label><input type="radio" name="contact_channel" value="email" {{ old('contact_channel') === 'email' ? 'checked' : '' }}> Email</label>
          <label><input type="radio" name="contact_channel" value="phone" {{ old('contact_channel') === 'phone' ? 'checked' : '' }}> Téléphone</label>
        </div>
        @error('contact_channel')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Centres d’intérêt (checkbox)</label>
        <div style="display:flex;gap:12px;flex-wrap:wrap;padding-top:10px;">
          @foreach(['crm' => 'CRM', 'facturation' => 'Facturation', 'stock' => 'Stock', 'projets' => 'Projets', 'support' => 'Support'] as $value => $label)
            <label><input type="checkbox" name="interests[]" value="{{ $value }}" {{ in_array($value, old('interests', []), true) ? 'checked' : '' }}> {{ $label }}</label>
          @endforeach
        </div>
        @error('interests')<span class="form-error">{{ $message }}</span>@enderror
        @error('interests.*')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Avatar (image)</label>
        <input type="file" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
        @error('avatar')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-3">
      <div class="form-group">
        <label class="form-label">Document (pdf/doc/xlsx)</label>
        <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
        @error('attachment')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="col-12">
      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }}>
        J’accepte les conditions *
      </label>
      @error('terms')<span class="form-error">{{ $message }}</span>@enderror
    </div>
  </div>

  <div class="form-actions" style="margin-top:18px;">
    <button type="submit" class="btn btn-primary" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Validation...">
      <i class="fas fa-shield-check"></i> Valider en AJAX
    </button>
  </div>
</form>

@if($saved)
  <div style="margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
    <strong>Dernière soumission validée :</strong>
    <pre style="margin-top:8px;white-space:pre-wrap;">{{ json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
  </div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('vendor/client/js/validation-demo.js') }}"></script>
@endpush
