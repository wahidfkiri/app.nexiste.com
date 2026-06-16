@extends('google-calendar::layouts.calendar')

@section('title', data_get($currentExtensionMeta, 'name', __('google-calendar::messages.page.title')))

@section('gc_breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('google-calendar::messages.breadcrumb.applications') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', __('google-calendar::messages.page.title')) }}</span>
@endsection

@section('gc_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-calendar-alt')), 'bg' => '#dbeafe', 'color' => '#2563eb', 'alt' => data_get($currentExtensionMeta, 'name', 'Google Calendar')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', __('google-calendar::messages.page.title')) }}</h1>
    </div>
    <p>{{ __('google-calendar::messages.page.subtitle') }}</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled>
        <i class="fas fa-database"></i> {{ __('google-calendar::messages.actions.migration_required') }}
      </button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-primary">
        <i class="fas fa-store"></i> {{ __('google-calendar::messages.actions.activate_marketplace') }}
      </a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gcSyncBtn">
        <i class="fas fa-rotate"></i> {{ __('google-calendar::messages.actions.sync') }}
      </button>
      <button class="btn btn-primary" id="gcCreateEventBtn" data-modal-open="gcEventModal">
        <i class="fas fa-plus"></i> {{ __('google-calendar::messages.actions.new_event') }}
      </button>
      <button class="btn btn-danger" id="gcDisconnectBtn">
        <i class="fas fa-link-slash"></i> {{ __('google-calendar::messages.actions.disconnect') }}
      </button>
    @else
      <a href="{{ route('google-calendar.oauth.connect') }}" class="btn btn-primary">
        <i class="fab fa-google"></i> {{ __('google-calendar::messages.actions.connect_google') }}
      </a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>{{ __('google-calendar::messages.storage.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-calendar::messages.storage.description') }}
    </p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);margin-bottom:10px;">
      php artisan migrate
    </div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>{{ __('google-calendar::messages.extension.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-calendar::messages.extension.description') }}
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-primary"><i class="fas fa-store"></i> {{ __('google-calendar::messages.extension.open_app_page') }}</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> {{ __('google-calendar::messages.extension.browse_apps') }}</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google"></i><h3>{{ __('google-calendar::messages.connection.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-calendar::messages.connection.description') }}
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-calendar.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> {{ __('google-calendar::messages.connection.connect_now') }}</a>
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-secondary"><i class="fas fa-store"></i> {{ __('google-calendar::messages.connection.open_marketplace') }}</a>
    </div>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatCalendars">0</div>
      <div class="stat-label">{{ __('google-calendar::messages.stats.calendars') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-sun"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatToday">0</div>
      <div class="stat-label">{{ __('google-calendar::messages.stats.events_today') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatMonth">0</div>
      <div class="stat-label">{{ __('google-calendar::messages.stats.this_month') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-forward"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatNext">0</div>
      <div class="stat-label">{{ __('google-calendar::messages.stats.next_30_days') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-flag"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatHolidays">0</div>
      <div class="stat-label">{{ __('google-calendar::messages.stats.holidays_year') }}</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>{{ __('google-calendar::messages.account.title') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">{{ __('google-calendar::messages.account.name') }}</span><span class="info-row-value">{{ $token?->google_name ?? __('google-calendar::messages.account.unknown') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-calendar::messages.account.email') }}</span><span class="info-row-value">{{ $token?->google_email ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-calendar::messages.account.connected') }}</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-calendar::messages.account.last_sync') }}</span><span class="info-row-value" id="gcLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? __('google-calendar::messages.account.never') }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-layer-group"></i><h3>{{ __('google-calendar::messages.calendars.title') }}</h3></div>
      <div class="info-card-body" style="padding:0;">
        <div id="gcCalendarsList" class="gc-calendar-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">{{ __('google-calendar::messages.table.events') }}</span>
        <span class="table-count" id="gcCount">{{ __('google-calendar::messages.table.count_results', ['count' => 0]) }}</span>
        <div class="table-spacer"></div>

        <div class="gc-view-switcher" id="gcViewModeSwitcher" role="tablist" aria-label="{{ __('google-calendar::messages.views.aria') }}">
          <button type="button" class="gc-view-btn active" data-gc-view-mode="month"><i class="fas fa-calendar"></i> {{ __('google-calendar::messages.views.month') }}</button>
          <button type="button" class="gc-view-btn" data-gc-view-mode="week"><i class="fas fa-calendar-week"></i> {{ __('google-calendar::messages.views.week') }}</button>
          <button type="button" class="gc-view-btn" data-gc-view-mode="day"><i class="fas fa-calendar-day"></i> {{ __('google-calendar::messages.views.day') }}</button>
          <button type="button" class="gc-view-btn" data-gc-view-mode="year"><i class="fas fa-calendar-alt"></i> {{ __('google-calendar::messages.views.year') }}</button>
          <button type="button" class="gc-view-btn" data-gc-view-mode="list"><i class="fas fa-list"></i> {{ __('google-calendar::messages.views.list') }}</button>
        </div>

        <div class="gc-period-nav" id="gcPeriodNav">
          <button type="button" class="btn btn-ghost btn-sm" id="gcPrevPeriod" title="{{ __('google-calendar::messages.period.previous') }}">
            <i class="fas fa-chevron-left"></i>
          </button>
          <button type="button" class="btn btn-ghost btn-sm" id="gcTodayPeriod" title="{{ __('google-calendar::messages.period.today') }}">{{ __('google-calendar::messages.period.today') }}</button>
          <button type="button" class="btn btn-ghost btn-sm" id="gcNextPeriod" title="{{ __('google-calendar::messages.period.next') }}">
            <i class="fas fa-chevron-right"></i>
          </button>
          <span class="gc-period-label" id="gcPeriodLabel"></span>
        </div>

        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gcSearchInput" placeholder="{{ __('google-calendar::messages.filters.search') }}" autocomplete="off">
        </div>

        <input type="date" class="filter-select gc-list-only" id="gcFromDate" title="{{ __('google-calendar::messages.filters.from') }}" style="width:140px;">
        <input type="date" class="filter-select gc-list-only" id="gcToDate" title="{{ __('google-calendar::messages.filters.to') }}" style="width:140px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c-ink-60);padding:0 4px;">
          <input type="checkbox" id="gcIncludeHolidays" checked>
          {{ __('google-calendar::messages.filters.include_holidays') }}
        </label>

        <button class="btn btn-ghost btn-sm" id="gcResetFilters" title="{{ __('google-calendar::messages.filters.reset') }}">
          <i class="fas fa-rotate-left"></i>
        </button>
      </div>

      <div id="gcListWrap">
        <table class="crm-table">
          <thead>
            <tr>
              <th>{{ __('google-calendar::messages.columns.title') }}</th>
              <th>{{ __('google-calendar::messages.columns.calendar') }}</th>
              <th>{{ __('google-calendar::messages.columns.start') }}</th>
              <th>{{ __('google-calendar::messages.columns.end') }}</th>
              <th>{{ __('google-calendar::messages.columns.status') }}</th>
              <th style="text-align:right;padding-right:20px;">{{ __('google-calendar::messages.columns.actions') }}</th>
            </tr>
          </thead>
          <tbody id="gcEventsTableBody"></tbody>
        </table>

        <div class="table-pagination">
          <span class="pagination-info" id="gcPaginationInfo"></span>
          <div class="pagination-spacer"></div>
          <div class="pagination-pages" id="gcPaginationControls"></div>
        </div>
      </div>

      <div id="gcCalendarModeWrap" class="gc-mode-wrap" hidden></div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gcEventModal">
  <div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-calendar-plus"></i>
      </div>
      <div>
        <div class="modal-title" id="gcEventModalTitle">{{ __('google-calendar::messages.modal.create_event') }}</div>
        <div class="modal-subtitle">{{ __('google-calendar::messages.modal.subtitle') }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="gcEventForm">
        <input type="hidden" id="gcEventCalendarId" name="calendar_id">
        <input type="hidden" id="gcEventId" name="event_id">
        <input type="hidden" id="gcSourceType" name="source_type">
        <input type="hidden" id="gcSourceId" name="source_id">
        <input type="hidden" id="gcSourceLabel" name="source_label">

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.title') }} <span class="required">*</span></label>
              <input type="text" class="form-control" id="gcSummary" name="summary" maxlength="255" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.start') }} <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gcStartAt" name="start_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.end') }} <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gcEndAt" name="end_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.location') }}</label>
              <input type="text" class="form-control" id="gcLocation" name="location" maxlength="500">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.detail.client_optional') }}</label>
              @if($clientsInstalled)
                <select class="form-control" id="gcClientId" name="client_id">
                  <option value="">{{ __('google-calendar::messages.common.none') }}</option>
                  @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                  @endforeach
                </select>
              @else
                <div class="gc-inline-note">
                  {{ __('google-calendar::messages.detail.client_module_missing') }}
                  <a href="{{ $clientsTargetUrl }}">{{ __('google-calendar::messages.detail.install_client_module') }}</a>
                </div>
              @endif
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.visibility') }}</label>
              <select class="form-control" id="gcVisibility" name="visibility">
                <option value="default">{{ __('google-calendar::messages.visibility.default') }}</option>
                <option value="public">{{ __('google-calendar::messages.visibility.public') }}</option>
                <option value="private">{{ __('google-calendar::messages.visibility.private') }}</option>
                <option value="confidential">{{ __('google-calendar::messages.visibility.confidential') }}</option>
              </select>
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.reminder') }}</label>
              <input type="number" class="form-control" id="gcReminder" name="reminder_minutes" min="1" max="40320" placeholder="{{ __('google-calendar::messages.form.reminder_placeholder') }}">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.attendees') }}</label>
              <input type="text" class="form-control" id="gcAttendees" name="attendees" placeholder="{{ __('google-calendar::messages.form.attendees_placeholder') }}">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('google-calendar::messages.form.description') }}</label>
              <textarea class="form-control" id="gcDescription" name="description" rows="4" maxlength="8000"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-calendar::messages.actions.cancel') }}</button>
      <button class="btn btn-primary" id="gcSaveEventBtn">
        <i class="fas fa-check"></i> {{ __('google-calendar::messages.actions.save_event') }}
      </button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gcEventDetailModal">
  <div class="modal modal-lg gc-detail-modal">
    <div class="modal-header">
      <div class="modal-header-icon" id="gcDetailModalIcon" style="background:#dbeafe;color:#2563eb">
        <i class="fas fa-calendar-day"></i>
      </div>
      <div>
        <div class="modal-title" id="gcDetailTitle">{{ __('google-calendar::messages.modal.detail_title') }}</div>
        <div class="modal-subtitle" id="gcDetailSubtitle">{{ __('google-calendar::messages.modal.detail_subtitle') }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="gc-detail-head">
        <div class="gc-detail-title-wrap">
          <div class="gc-detail-calendar-pill" id="gcDetailCalendarPill">
            <span class="gc-detail-calendar-dot" id="gcDetailCalendarDot"></span>
            <span id="gcDetailCalendarName">{{ __('google-calendar::messages.columns.calendar') }}</span>
          </div>
          <h2 class="gc-detail-event-title" id="gcDetailEventTitle">{{ __('google-calendar::messages.common.no_title') }}</h2>
        </div>
        <div class="gc-detail-status" id="gcDetailStatus"></div>
      </div>

      <div class="gc-detail-grid">
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.when') }}</div>
          <div class="gc-detail-value" id="gcDetailWhen">-</div>
        </div>
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.location') }}</div>
          <div class="gc-detail-value" id="gcDetailLocation">-</div>
        </div>
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.client') }}</div>
          <div class="gc-detail-value" id="gcDetailClient">-</div>
        </div>
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.source') }}</div>
          <div class="gc-detail-value" id="gcDetailSource">-</div>
        </div>
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.visibility') }}</div>
          <div class="gc-detail-value" id="gcDetailVisibility">-</div>
        </div>
        <div class="gc-detail-item">
          <div class="gc-detail-label">{{ __('google-calendar::messages.detail.updated_at') }}</div>
          <div class="gc-detail-value" id="gcDetailUpdatedAt">-</div>
        </div>
      </div>

      <div class="gc-detail-section">
        <div class="gc-detail-label">{{ __('google-calendar::messages.detail.attendees') }}</div>
        <div class="gc-detail-attendees" id="gcDetailAttendees">{{ __('google-calendar::messages.detail.no_attendees') }}</div>
      </div>

      <div class="gc-detail-section">
        <div class="gc-detail-label">{{ __('google-calendar::messages.detail.description') }}</div>
        <div class="gc-detail-description" id="gcDetailDescription">{{ __('google-calendar::messages.detail.no_description') }}</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-calendar::messages.actions.close') }}</button>
      <button class="btn btn-secondary" id="gcDetailOpenGoogleBtn" hidden>
        <i class="fas fa-arrow-up-right-from-square"></i> {{ __('google-calendar::messages.actions.open_google') }}
      </button>
      <button class="btn btn-secondary" id="gcDetailEditBtn">
        <i class="fas fa-pen"></i> {{ __('google-calendar::messages.actions.edit') }}
      </button>
      <button class="btn btn-danger" id="gcDetailDeleteBtn">
        <i class="fas fa-trash"></i> {{ __('google-calendar::messages.actions.delete') }}
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GCAL_ROUTES = {
  connect: '{{ route('google-calendar.oauth.connect') }}',
  disconnect: '{{ route('google-calendar.oauth.disconnect') }}',
  calendarsData: '{{ route('google-calendar.calendars.data') }}',
  selectCalendar: '{{ route('google-calendar.calendar.select') }}',
  eventsData: '{{ route('google-calendar.events.data') }}',
  eventsStore: '{{ route('google-calendar.events.store') }}',
  eventsBase: @json(rtrim(route('google-calendar.index'), '/') . '/events'),
  stats: '{{ route('google-calendar.stats') }}',
  sync: '{{ route('google-calendar.sync') }}',
};

window.GCAL_BOOTSTRAP = {
  connected: @json((bool) $connected),
  selectedCalendarId: @json($token?->selected_calendar_id),
  timezone: @json(config('google-calendar.defaults.timezone', 'UTC')),
  includeHolidays: true,
  viewMode: 'month',
  locale: 'fr-FR',
  clientsInstalled: @json((bool) $clientsInstalled),
  prefill: @json($prefill ?? []),
  i18n: @json($jsI18n),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleCalendarModule) {
    window.GoogleCalendarModule.boot(window.GCAL_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('google-calendar::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error(@json(__('google-calendar::messages.common.error')), @json(session('error')));
  @endif
});
</script>
@endpush
