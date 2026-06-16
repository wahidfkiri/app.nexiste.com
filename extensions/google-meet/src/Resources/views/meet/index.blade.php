@extends('google-meet::layouts.meet')

@section('title', data_get($currentExtensionMeta, 'name', 'Google Meet'))

@section('gm_breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('google-meet::messages.breadcrumb.applications') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Google Meet') }}</span>
@endsection

@section('gm_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-video')), 'bg' => '#dcfce7', 'color' => '#34a853', 'alt' => data_get($currentExtensionMeta, 'name', 'Google Meet')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Google Meet') }}</h1>
    </div>
    <p>{{ __('google-meet::messages.page.subtitle') }}</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled>
        <i class="fas fa-database"></i> {{ __('google-meet::messages.actions.migration_required') }}
      </button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-primary">
        <i class="fas fa-store"></i> {{ __('google-meet::messages.actions.activate_marketplace') }}
      </a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gmSyncBtn">
        <i class="fas fa-rotate"></i> {{ __('google-meet::messages.actions.sync') }}
      </button>
      <button class="btn btn-primary" id="gmCreateMeetingBtn" data-modal-open="gmMeetingModal">
        <i class="fas fa-plus"></i> {{ __('google-meet::messages.actions.new_meeting') }}
      </button>
      <button class="btn btn-danger" id="gmDisconnectBtn">
        <i class="fas fa-link-slash"></i> {{ __('google-meet::messages.actions.disconnect') }}
      </button>
    @else
      <a href="{{ route('google-meet.oauth.connect') }}" class="btn btn-primary">
        <i class="fab fa-google"></i> {{ __('google-meet::messages.actions.connect_google_meet') }}
      </a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>{{ __('google-meet::messages.storage.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-meet::messages.storage.description') }}
    </p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:'DM Sans', sans-serif;font-size:12px;color:var(--c-ink-80);margin-bottom:10px;">
      {{ __('google-meet::messages.storage.command') }}
    </div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>{{ __('google-meet::messages.extension.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-meet::messages.extension.description') }}
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-primary"><i class="fas fa-store"></i> {{ __('google-meet::messages.actions.open_app') }}</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> {{ __('google-meet::messages.actions.explore_apps') }}</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google"></i><h3>{{ __('google-meet::messages.connection.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-meet::messages.connection.description') }}
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-meet.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> {{ __('google-meet::messages.actions.connect') }}</a>
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-secondary"><i class="fas fa-store"></i> {{ __('google-meet::messages.actions.open_marketplace') }}</a>
    </div>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatCalendars">0</div>
      <div class="stat-label">{{ __('google-meet::messages.stats.calendars') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-video"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatToday">0</div>
      <div class="stat-label">{{ __('google-meet::messages.stats.today') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatWeek">0</div>
      <div class="stat-label">{{ __('google-meet::messages.stats.next_7_days') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatMonth">0</div>
      <div class="stat-label">{{ __('google-meet::messages.stats.this_month') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-link"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatLinks">0</div>
      <div class="stat-label">{{ __('google-meet::messages.stats.active_links') }}</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>{{ __('google-meet::messages.account.title') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">{{ __('google-meet::messages.account.name') }}</span><span class="info-row-value">{{ $token?->google_name ?? __('google-meet::messages.common.unknown') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-meet::messages.account.email') }}</span><span class="info-row-value">{{ $token?->google_email ?? __('google-meet::messages.common.dash') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-meet::messages.account.connected_at') }}</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? __('google-meet::messages.common.dash') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-meet::messages.account.last_sync') }}</span><span class="info-row-value" id="gmLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? __('google-meet::messages.common.never') }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-layer-group"></i><h3>{{ __('google-meet::messages.calendars.title') }}</h3></div>
      <div class="info-card-body" style="padding:0;">
        <div id="gmCalendarsList" class="gm-calendar-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">{{ __('google-meet::messages.table.meetings') }}</span>
        <span class="table-count" id="gmCount">{{ __('google-meet::messages.table.count_results', ['count' => 0]) }}</span>
        <div class="table-spacer"></div>

        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gmSearchInput" placeholder="{{ __('google-meet::messages.filters.search') }}" autocomplete="off">
        </div>

        <input type="date" class="filter-select" id="gmFromDate" title="{{ __('google-meet::messages.filters.from') }}" style="width:140px;">
        <input type="date" class="filter-select" id="gmToDate" title="{{ __('google-meet::messages.filters.to') }}" style="width:140px;">

        <button class="btn btn-ghost btn-sm" id="gmResetFilters" title="{{ __('google-meet::messages.actions.reset') }}">
          <i class="fas fa-rotate-left"></i>
        </button>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>{{ __('google-meet::messages.columns.meeting') }}</th>
            <th>{{ __('google-meet::messages.columns.calendar') }}</th>
            <th>{{ __('google-meet::messages.columns.start') }}</th>
            <th>{{ __('google-meet::messages.columns.end') }}</th>
            <th>{{ __('google-meet::messages.columns.status') }}</th>
            <th style="text-align:right;padding-right:20px;">{{ __('google-meet::messages.columns.actions') }}</th>
          </tr>
        </thead>
        <tbody id="gmMeetingsTableBody"></tbody>
      </table>

      <div class="table-pagination">
        <span class="pagination-info" id="gmPaginationInfo"></span>
        <div class="pagination-spacer"></div>
        <div class="pagination-pages" id="gmPaginationControls"></div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gmMeetingModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-video"></i>
      </div>
      <div>
        <div class="modal-title" id="gmMeetingModalTitle">{{ __('google-meet::messages.modal.create_meeting') }}</div>
        <div class="modal-subtitle">{{ __('google-meet::messages.modal.subtitle') }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="gmMeetingForm">
        <input type="hidden" id="gmMeetingCalendarId" name="calendar_id">
        <input type="hidden" id="gmMeetingEventId" name="event_id">

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.title') }} <span class="required">*</span></label>
              <input type="text" class="form-control" id="gmSummary" name="summary" maxlength="255" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.start') }} <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gmStartAt" name="start_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.end') }} <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gmEndAt" name="end_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.location') }}</label>
              <input type="text" class="form-control" id="gmLocation" name="location" maxlength="500" placeholder="{{ __('google-meet::messages.form.location_placeholder') }}">
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.visibility') }}</label>
              <select class="form-control" id="gmVisibility" name="visibility">
                <option value="default">{{ __('google-meet::messages.visibility.default') }}</option>
                <option value="public">{{ __('google-meet::messages.visibility.public') }}</option>
                <option value="private">{{ __('google-meet::messages.visibility.private') }}</option>
                <option value="confidential">{{ __('google-meet::messages.visibility.confidential') }}</option>
              </select>
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.notifications') }}</label>
              <select class="form-control" id="gmSendUpdates" name="send_updates">
                <option value="all">{{ __('google-meet::messages.notifications.all') }}</option>
                <option value="externalOnly">{{ __('google-meet::messages.notifications.external_only') }}</option>
                <option value="none">{{ __('google-meet::messages.notifications.none') }}</option>
              </select>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.attendees') }}</label>
              <div class="gm-tag-input" id="gmParticipantsField">
                <div class="gm-tag-list" id="gmAttendeesBadges"></div>
                <input type="text" class="gm-tag-text" id="gmAttendeesInput" placeholder="{{ __('google-meet::messages.form.attendees_placeholder') }}">
              </div>
              <input type="hidden" id="gmAttendees" name="attendees" value="">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" id="gmCreateMeetLink" name="create_meet_link" value="1" checked>
              <label class="form-label" style="margin-bottom:0;">{{ __('google-meet::messages.form.auto_meet_link') }}</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-meet::messages.form.description') }}</label>
              <textarea class="form-control" id="gmDescription" name="description" rows="4" maxlength="8000"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-meet::messages.actions.cancel') }}</button>
      <button class="btn btn-primary" id="gmSaveMeetingBtn">
        <i class="fas fa-check"></i> {{ __('google-meet::messages.actions.save') }}
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GMEET_ROUTES = {
  connect: '{{ route('google-meet.oauth.connect') }}',
  disconnect: '{{ route('google-meet.oauth.disconnect') }}',
  calendarsData: '{{ route('google-meet.calendars.data') }}',
  selectCalendar: '{{ route('google-meet.calendar.select') }}',
  meetingsData: '{{ route('google-meet.meetings.data') }}',
  meetingsStore: '{{ route('google-meet.meetings.store') }}',
  meetingsBase: @json(rtrim(route('google-meet.index'), '/') . '/meetings'),
  stats: '{{ route('google-meet.stats') }}',
  sync: '{{ route('google-meet.sync') }}',
};

window.GMEET_BOOTSTRAP = {
  connected: @json((bool) $connected),
  selectedCalendarId: @json($token?->selected_calendar_id),
  timezone: @json(config('google-meet.defaults.timezone', 'Europe/Paris')),
  googleCalendarInstalled: @json((bool) $googleCalendarInstalled),
  googleCalendarTargetUrl: @json($googleCalendarTargetUrl),
};

window.GMEET_I18N = @json(\Illuminate\Support\Facades\Lang::get('google-meet::messages'));

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleMeetModule) {
    window.GoogleMeetModule.boot(window.GMEET_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('google-meet::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error(@json(__('google-meet::messages.common.error')), @json(session('error')));
  @endif
});
</script>
@endpush
