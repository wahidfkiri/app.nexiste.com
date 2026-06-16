@extends('client::layouts.crm')

@section('title', $client->company_name)

@section('breadcrumb')
  <a href="{{ route('clients.index') }}">{{ __('client::clients.pages.index.title') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $client->company_name }}</span>
@endsection

@section('content')
@php($paymentTerms = trans('client::clients.payment_terms'))
@php($avatarPalette = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'])
@php($avatarName = trim((string) ($client->company_name ?: 'Client')))
@php($avatarSeed = abs(crc32($avatarName)))
@php($avatarColor = $avatarPalette[$avatarSeed % count($avatarPalette)] ?? '#2563eb')
@php($avatarInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($avatarName, 0, 2)))

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $avatarColor ?? '#2563eb' }};color:#fff;display:flex;align-items:center;justify-content:center;font-family: "DM Sans", sans-serif;font-size:20px;font-weight:700;flex-shrink:0;">
      {{ $avatarInitials ?? 'CL' }}
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $client->company_name }}</h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $client->status }}">{{ $client->status_label }}</span>
        <span class="badge badge-{{ $client->type }}">{{ $client->type_label }}</span>
        @if($client->source)
          <span style="font-size:12px;color:var(--c-ink-40)"><i class="fas fa-arrow-right-to-bracket" style="margin-right:4px;"></i>{{ $client->source_label }}</span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40)"><i class="fas fa-calendar" style="margin-right:4px;"></i>{{ __('client::clients.pages.show.client_since', ['date' => $client->created_at->translatedFormat('M Y')]) }}</span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary"><i class="fas fa-pen"></i> {{ __('client::clients.actions.edit') }}</a>
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle><i class="fas fa-ellipsis"></i></button>
      <div class="dropdown-menu">
        <a href="{{ route('clients.edit', $client) }}" class="dropdown-item"><i class="fas fa-pen"></i> {{ __('client::clients.actions.edit') }}</a>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item danger" onclick="deleteThisClient()"><i class="fas fa-trash"></i> {{ __('client::clients.actions.delete') }}</button>
      </div>
    </div>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card"><div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-euro-sign"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($client->revenue ?? 0, 0, ',', ' ') }} €</div><div class="stat-label">{{ __('client::clients.stats.revenue') }}</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-star"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($client->potential_value ?? 0, 0, ',', ' ') }} €</div><div class="stat-label">{{ __('client::clients.stats.potential') }}</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-users"></i></div><div class="stat-body"><div class="stat-value">{{ $client->employee_count ?? '—' }}</div><div class="stat-label">{{ __('client::clients.stats.employees') }}</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-check"></i></div><div class="stat-body"><div class="stat-value" style="font-size:16px;">{{ $client->next_follow_up_at ? $client->next_follow_up_at->format('d M') : '—' }}</div><div class="stat-label">{{ __('client::clients.stats.next_follow_up') }}</div>@if($client->next_follow_up_at && $client->next_follow_up_at->isPast())<span class="stat-trend down"><i class="fas fa-exclamation"></i> {{ __('client::clients.stats.late') }}</span>@elseif($client->next_follow_up_at)<span class="stat-trend up"><i class="fas fa-clock"></i> {{ __('client::clients.stats.in', ['time' => $client->next_follow_up_at->diffForHumans()]) }}</span>@endif</div></div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-address-card"></i><h3>{{ __('client::clients.sections.contact') }}</h3></div>
      <div class="info-card-body">
        @if($client->contact_name)<div class="info-row"><span class="info-row-label"><i class="fas fa-user" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.contact_name') }}</span><span class="info-row-value fw-medium">{{ $client->contact_name }}</span></div>@endif
        <div class="info-row"><span class="info-row-label"><i class="fas fa-envelope" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.email') }}</span><span class="info-row-value"><a href="mailto:{{ $client->email }}" style="color:var(--c-accent);text-decoration:none;">{{ $client->email }}</a></span></div>
        @if($client->phone)<div class="info-row"><span class="info-row-label"><i class="fas fa-phone" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.phone') }}</span><span class="info-row-value"><a href="tel:{{ $client->phone }}" style="color:inherit;text-decoration:none;">{{ $client->phone }}</a></span></div>@endif
        @if($client->mobile)<div class="info-row"><span class="info-row-label"><i class="fas fa-mobile" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.mobile') }}</span><span class="info-row-value"><a href="tel:{{ $client->mobile }}" style="color:inherit;text-decoration:none;">{{ $client->mobile }}</a></span></div>@endif
        @if($client->website)<div class="info-row"><span class="info-row-label"><i class="fas fa-globe" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.website') }}</span><span class="info-row-value"><a href="{{ $client->website }}" target="_blank" rel="noopener" style="color:var(--c-accent);text-decoration:none;">{{ $client->website }}</a></span></div>@endif
        @if($client->full_address)<div class="info-row"><span class="info-row-label"><i class="fas fa-location-dot" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('client::clients.fields.address') }}</span><span class="info-row-value">{{ $client->full_address }}</span></div>@endif
      </div>
    </div>

    @if($client->notes)
    <div class="info-card" style="margin-bottom:16px;"><div class="info-card-header"><i class="fas fa-note-sticky"></i><h3>{{ __('client::clients.sections.notes') }}</h3></div><div class="info-card-body"><p style="font-size:13.5px;color:var(--c-ink-60);line-height:1.7;margin:0;">{{ $client->notes }}</p></div></div>
    @endif

    @if(!empty($client->tags))
    <div class="info-card"><div class="info-card-header"><i class="fas fa-tags"></i><h3>{{ __('client::clients.fields.tags') }}</h3></div><div class="info-card-body" style="display:flex;flex-wrap:wrap;gap:8px;">@foreach($client->tags as $tag)<span class="badge" style="background:var(--c-accent-lt);color:var(--c-accent);font-size:12px;">{{ $tag }}</span>@endforeach</div></div>
    @endif
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;"><div class="info-card-header"><i class="fas fa-chart-bar"></i><h3>{{ __('client::clients.sections.business') }}</h3></div><div class="info-card-body"><div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.type') }}</span><span class="badge badge-{{ $client->type }}">{{ $client->type_label }}</span></div><div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.status') }}</span><span class="badge badge-{{ $client->status }}">{{ $client->status_label }}</span></div>@if($client->source)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.source') }}</span><span class="info-row-value">{{ $client->source_label }}</span></div>@endif @if($client->industry)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.industry') }}</span><span class="info-row-value">{{ $client->industry }}</span></div>@endif @if($client->payment_term)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.payment_term') }}</span><span class="info-row-value">{{ $paymentTerms[$client->payment_term] ?? $client->payment_term }}</span></div>@endif @if($client->vat_number)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.vat_number_short') }}</span><span class="info-row-value" style="font-family: "DM Sans", sans-serif;font-size:12px;">{{ $client->vat_number }}</span></div>@endif @if($client->siret)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.siret') }}</span><span class="info-row-value" style="font-family: "DM Sans", sans-serif;font-size:12px;">{{ $client->siret }}</span></div>@endif<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.created_at') }}</span><span class="info-row-value">{{ $client->created_at->format('d/m/Y') }}</span></div>@if($client->last_contact_at)<div class="info-row"><span class="info-row-label">{{ __('client::clients.fields.last_contact_at') }}</span><span class="info-row-value">{{ $client->last_contact_at->format('d/m/Y') }}</span></div>@endif</div></div>

    <div class="info-card"><div class="info-card-header"><i class="fas fa-bolt"></i><h3>{{ __('client::clients.sections.quick_actions') }}</h3></div><div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;"><a href="mailto:{{ $client->email }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-envelope"></i> {{ __('client::clients.actions.send_email') }}</a>@if($client->phone)<a href="tel:{{ $client->phone }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-phone"></i> {{ __('client::clients.actions.call') }}</a>@endif<a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-pen"></i> {{ __('client::clients.actions.edit_profile') }}</a><button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteThisClient()"><i class="fas fa-trash"></i> {{ __('client::clients.actions.delete') }}</button></div></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
async function deleteThisClient() {
  Modal.confirm({
    title: @json(__('client::clients.confirmations.delete_title')),
    message: @json(__('client::clients.confirmations.delete_message', ['name' => $client->company_name])),
    confirmText: @json(__('client::clients.actions.delete')),
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete('{{ route("clients.destroy", $client) }}');
      if (ok) {
        Toast.success(@json(__('client::clients.messages.deleted')), data.message);
        setTimeout(() => window.location.href = '{{ route('clients.index') }}', 900);
      } else {
        Toast.error(@json(__('client::clients.messages.unexpected_error')), data.message || @json(__('client::clients.messages.delete_error', ['error' => ''])));
      }
    },
  });
}
</script>
@endpush
