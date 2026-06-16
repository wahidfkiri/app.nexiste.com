@extends('chatbot::layouts.chatbot')

@section('title', data_get($currentExtensionMeta, 'name', 'Chatbot'))

@section('chatbot_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Chatbot') }}</span>
@endsection

@section('chatbot_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-comments')), 'bg' => '#e0f2fe', 'color' => '#0ea5e9', 'alt' => data_get($currentExtensionMeta, 'name', 'Chatbot')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Chatbot') }}</h1>
    </div>
    <p>Messagerie collaborative en temps reel avec salons, emojis et partage de fichiers.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-secondary" id="cbRefreshBtn"><i class="fas fa-rotate"></i> Actualiser</button>
    <button class="btn btn-primary" id="cbNewRoomBtn"><i class="fas fa-plus"></i> Nouveau salon</button>
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de donnees requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Chatbot sont absentes. Executez la migration avant utilisation.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@else
<div class="cb-stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-comments"></i></div>
    <div class="stat-body"><div class="stat-value" id="cbStatRooms">0</div><div class="stat-label">Salons</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-lock"></i></div>
    <div class="stat-body"><div class="stat-value" id="cbStatPrivateRooms">0</div><div class="stat-label">Salons prives</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-message"></i></div>
    <div class="stat-body"><div class="stat-value" id="cbStatToday">0</div><div class="stat-label">Messages aujourd hui</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body"><div class="stat-value" id="cbStatWeek">0</div><div class="stat-label">7 derniers jours</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2;color:#ef4444"><i class="fas fa-broadcast-tower"></i></div>
    <div class="stat-body"><div class="stat-value" id="cbSocketStatus">{{ $socketEnabled ? 'Actif' : 'Off' }}</div><div class="stat-label">Socket.IO</div></div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card cb-rooms-card">
      <div class="info-card-header">
        <i class="fas fa-layer-group"></i>
        <h3>Salons & Rooms</h3>
      </div>
      <div class="info-card-body" style="padding:0;">
        <div class="cb-rooms-topbar">
          <div class="table-search">
            <i class="fas fa-search"></i>
            <input type="text" id="cbRoomSearchInput" placeholder="Rechercher un salon..." autocomplete="off">
          </div>
        </div>
        <div id="cbRoomsList" class="cb-rooms-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper cb-chat-wrap">
      <div class="table-header">
        <span class="table-title" id="cbRoomTitle">Selectionnez un salon</span>
        <span class="table-count" id="cbCount">0 message(s)</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="cbMessageSearchInput" placeholder="Rechercher dans les messages..." autocomplete="off">
        </div>
        <button class="btn btn-ghost btn-sm" id="cbEditRoomBtn" title="Modifier le salon" disabled><i class="fas fa-pen"></i></button>
        <button class="btn btn-ghost btn-sm" id="cbDeleteRoomBtn" title="Supprimer le salon" disabled><i class="fas fa-trash"></i></button>
      </div>

      <div id="cbMessagesList" class="cb-messages-list"></div>

      <div class="cb-compose">
        <form id="cbComposeForm" enctype="multipart/form-data">
          <input type="hidden" id="cbComposeRoomId" name="room_id" value="">
          <input type="hidden" id="cbReplyToMessageId" name="reply_to_message_id" value="">

          <div class="cb-compose-tools">
            <button type="button" class="btn btn-secondary btn-sm" id="cbEmojiBtn" title="Emojis"><i class="far fa-face-smile"></i> Emojis</button>
            <label class="btn btn-secondary btn-sm cb-file-btn" title="Ajouter des fichiers">
              <i class="fas fa-paperclip"></i> Fichiers
              <input type="file" id="cbFileInput" name="files[]" multiple accept="{{ collect($allowedExtensions ?? [])->map(fn($ext) => '.' . ltrim((string) $ext, '.'))->implode(',') }}">
            </label>
            <span class="cb-compose-note">Formats: {{ strtoupper(collect($allowedExtensions ?? [])->implode(', ')) }} - Max {{ number_format((($maxFileSizeKb ?? 10240) / 1024), 1) }} MB</span>
          </div>

          <div class="cb-file-chips" id="cbFileChips"></div>

          <div class="cb-compose-row">
            <textarea class="form-control" id="cbComposeText" name="text" rows="3" maxlength="10000" placeholder="Ecrire un message a l equipe..."></textarea>
            <div class="cb-compose-actions">
              <button type="submit" class="btn btn-primary" id="cbSendBtn" data-loading-text="Envoi...">
                <i class="fas fa-paper-plane"></i> Envoyer
              </button>
            </div>
          </div>

          <div class="cb-emoji-panel" id="cbEmojiPanel"></div>
        </form>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="cbRoomModal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="fas fa-comments"></i></div>
      <div>
        <div class="modal-title" id="cbRoomModalTitle">Nouveau salon</div>
        <div class="modal-subtitle">Configurez le salon de discussion</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>

    <form id="cbRoomForm">
      <div class="modal-body">
        <input type="hidden" id="cbRoomId" value="">
        <input type="hidden" id="cbRoomIcon" name="icon" value="fa-comments">
        <input type="hidden" id="cbRoomColor" name="color" value="#0ea5e9">

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Nom du salon <span class="required">*</span></label>
              <input type="text" class="form-control" id="cbRoomName" name="name" maxlength="120" required>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="cbRoomDescription" name="description" rows="3" maxlength="2000" placeholder="Objectif du salon, contexte..."></textarea>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Icône</label>
              <div class="cb-icon-picker" id="cbIconPicker">
                @foreach(($iconChoices ?? []) as $icon)
                  <button type="button" class="cb-icon-choice {{ $loop->first ? 'active' : '' }}" data-cb-icon-choice="{{ $icon }}" title="{{ $icon }}">
                    <i class="fas {{ $icon }}"></i>
                  </button>
                @endforeach
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Couleur</label>
              <div class="cb-color-picker" id="cbColorPicker">
                @foreach(($colorPalette ?? []) as $hex)
                  <button type="button" class="cb-color-choice {{ $loop->first ? 'active' : '' }}" data-cb-color-choice="{{ $hex }}" style="--cb-color: {{ $hex }};" title="{{ $hex }}"></button>
                @endforeach
              </div>
              <div class="cb-color-custom">
                <input type="color" class="form-control" id="cbRoomColorCustom" value="{{ ($colorPalette[0] ?? '#0ea5e9') }}" style="height:40px;max-width:72px;">
                <span id="cbRoomColorLabel">{{ ($colorPalette[0] ?? '#0ea5e9') }}</span>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Participants (optionnel)</label>
              <div class="cb-member-picker">
                <input type="text" class="form-control" id="cbMemberSearch" placeholder="Chercher un utilisateur...">
                <div class="cb-member-suggest" id="cbMemberSuggest"></div>
                <div class="cb-member-badges" id="cbMemberBadges"></div>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group" style="padding-top:28px;">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" id="cbRoomPrivate" name="is_private" value="1">
                <span>Salon prive</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
        <button type="submit" class="btn btn-primary" id="cbRoomSaveBtn" data-loading-text="Enregistrement...">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.CHATBOT_ROUTES = {
  roomsData: '{{ route('chatbot.rooms.data') }}',
  usersData: '{{ route('chatbot.users.data') }}',
  roomStore: '{{ route('chatbot.rooms.store') }}',
  roomUpdateBase: @json(rtrim(route('chatbot.index'), '/') . '/rooms'),
  roomDeleteBase: @json(rtrim(route('chatbot.index'), '/') . '/rooms'),
  messagesData: '{{ route('chatbot.messages.data') }}',
  searchData: '{{ route('chatbot.search.data') }}',
  messageSend: '{{ route('chatbot.messages.send') }}',
  messageDeleteBase: @json(rtrim(route('chatbot.index'), '/') . '/messages'),
  stats: '{{ route('chatbot.stats') }}',
};

window.CHATBOT_BOOTSTRAP = {
  enabled: @json((bool) $storageReady),
  tenantId: @json((int) auth()->user()->tenant_id),
  userId: @json((int) auth()->id()),
  userName: @json((string) (auth()->user()->name ?? 'Utilisateur')),
  socket: {
    enabled: @json((bool) $socketEnabled),
    clientUrl: @json((string) $socketClientUrl),
    path: @json((string) $socketPath),
    namespace: @json((string) $socketNamespace),
    transports: @json((array) config('chatbot.socket.transports', ['websocket', 'polling'])),
  },
  files: {
    maxSizeKb: @json((int) ($maxFileSizeKb ?? 10240)),
    allowedMimeTypes: @json((array) ($allowedMimeTypes ?? [])),
    allowedExtensions: @json((array) ($allowedExtensions ?? [])),
  },
  messages: {
    maxFetch: @json((int) config('chatbot.messages.max_fetch', 300)),
  },
  ui: {
    iconChoices: @json((array) ($iconChoices ?? [])),
    colorPalette: @json((array) ($colorPalette ?? [])),
  },
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.ChatbotModule) {
    window.ChatbotModule.boot(window.CHATBOT_BOOTSTRAP);
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
