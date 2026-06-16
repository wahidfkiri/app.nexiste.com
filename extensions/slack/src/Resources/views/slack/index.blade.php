@extends('slack::layouts.slack')

@section('title', data_get($currentExtensionMeta, 'name', 'Slack'))

@section('slack_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Slack') }}</span>
@endsection

@section('slack_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fab fa-slack')), 'bg' => '#f3e8ff', 'color' => '#4A154B', 'alt' => data_get($currentExtensionMeta, 'name', 'Slack')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Slack') }}</h1>
    </div>
    <p>Messagerie equipe Slack avec synchronisation API et temps reel Socket.IO.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'slack') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis Marketplace</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="slSyncBtn"><i class="fas fa-rotate"></i> Synchroniser</button>
      <button class="btn btn-danger" id="slDisconnectBtn"><i class="fas fa-link-slash"></i> Deconnecter</button>
    @else
      <a href="{{ route('slack.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-slack"></i> Connecter Slack</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de donnees requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Slack sont absentes. Executez la migration avant utilisation.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension non activee</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Slack est installee mais non activee pour ce tenant. Activez-la depuis Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'slack') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-slack"></i><h3>Connexion Slack</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Connectez votre workspace Slack pour recuperer les canaux et messages.</p>
    <a href="{{ route('slack.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-slack"></i> Se connecter</a>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#4a154b1f;color:#4a154b"><i class="fab fa-slack"></i></div>
    <div class="stat-body"><div class="stat-value" id="slStatTeam">{{ $token?->team_name ?: '-' }}</div><div class="stat-label">Workspace</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-hashtag"></i></div>
    <div class="stat-body"><div class="stat-value" id="slStatChannels">0</div><div class="stat-label">Canaux</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-message"></i></div>
    <div class="stat-body"><div class="stat-value" id="slStatToday">0</div><div class="stat-label">Messages aujourd hui</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body"><div class="stat-value" id="slStatWeek">0</div><div class="stat-label">7 derniers jours</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-broadcast-tower"></i></div>
    <div class="stat-body"><div class="stat-value" id="slSocketStatus">{{ $socketEnabled ? 'Actif' : 'Off' }}</div><div class="stat-label">Socket.IO</div></div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Compte connecte</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Equipe</span><span class="info-row-value">{{ $token?->team_name ?: 'Inconnue' }}</span></div>
        <div class="info-row"><span class="info-row-label">Team ID</span><span class="info-row-value">{{ $token?->team_id ?: '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connecte le</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Derniere sync</span><span class="info-row-value" id="slLastSync">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?: 'Jamais' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-list"></i><h3>Canaux Slack</h3></div>
      <div class="info-card-body" style="padding:0;">
        <div id="slChannelsList" class="sl-channel-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper sl-messages-wrap">
      <div class="table-header">
        <span class="table-title" id="slChannelTitle">Messages</span>
        <span class="table-count" id="slCount">0 resultat(s)</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="slSearchInput" placeholder="Rechercher dans les messages..." autocomplete="off">
        </div>
        <button class="btn btn-ghost btn-sm" id="slResetFilters" title="Reinitialiser"><i class="fas fa-rotate-left"></i></button>
      </div>

      <div id="slMessagesList" class="sl-messages-list"></div>

      <div class="table-pagination">
        <span class="pagination-info" id="slPaginationInfo"></span>
        <div class="pagination-spacer"></div>
        <div class="pagination-pages" id="slPaginationControls"></div>
      </div>

      <div class="sl-compose">
        <form id="slComposeForm">
          <input type="hidden" id="slComposeChannelId" name="channel_id" value="">
          <input type="hidden" id="slComposeThreadTs" name="thread_ts" value="">
          <div class="sl-compose-input-wrap">
            <textarea class="form-control" id="slComposeText" name="text" rows="2" maxlength="40000" placeholder="Ecrire un message Slack..." required></textarea>
            <button type="submit" class="btn btn-primary" id="slSendBtn" data-loading-text="Envoi...">
              <i class="fas fa-paper-plane"></i> Envoyer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
window.SLACK_ROUTES = {
  connect: '{{ route('slack.oauth.connect') }}',
  disconnect: '{{ route('slack.oauth.disconnect') }}',
  channelsData: '{{ route('slack.channels.data') }}',
  selectChannel: '{{ route('slack.channel.select') }}',
  messagesData: '{{ route('slack.messages.data') }}',
  messageSend: '{{ route('slack.messages.send') }}',
  stats: '{{ route('slack.stats') }}',
  sync: '{{ route('slack.sync') }}',
};

window.SLACK_BOOTSTRAP = {
  connected: @json((bool) $connected),
  selectedChannelId: @json($token?->selected_channel_id),
  tenantId: @json((int) auth()->user()->tenant_id),
  socket: {
    enabled: @json((bool) $socketEnabled),
    clientUrl: @json((string) $socketClientUrl),
    path: @json((string) $socketPath),
    namespace: @json((string) $socketNamespace),
    transports: @json((array) config('slack.socket.transports', ['websocket', 'polling'])),
  },
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.SlackModule) {
    window.SlackModule.boot(window.SLACK_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success('Succes', @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error('Erreur', @json(session('error')));
  @endif
});
</script>
@endpush
