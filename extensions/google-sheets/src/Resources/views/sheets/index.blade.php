@extends('google-sheets::layouts.sheets')

@section('title', data_get($currentExtensionMeta, 'name', __('google-sheets::messages.page.title')))

@section('gs_breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('google-sheets::messages.breadcrumb.applications') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', __('google-sheets::messages.page.title')) }}</span>
@endsection

@section('gs_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-file-excel')), 'bg' => '#dcfce7', 'color' => '#0f9d58', 'alt' => data_get($currentExtensionMeta, 'name', __('google-sheets::messages.page.title'))])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', __('google-sheets::messages.page.title')) }}</h1>
    </div>
    <p>{{ __('google-sheets::messages.page.subtitle') }}</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> {{ __('google-sheets::messages.actions.migration_required') }}</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> {{ __('google-sheets::messages.actions.activate_marketplace') }}</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gsRefreshBtn"><i class="fas fa-rotate"></i> {{ __('google-sheets::messages.actions.refresh') }}</button>
      <button class="btn btn-primary" id="gsCreateBtn" data-modal-open="gsCreateModal"><i class="fas fa-plus"></i> {{ __('google-sheets::messages.actions.new_spreadsheet') }}</button>
      <button class="btn btn-danger" id="gsDisconnectBtn"><i class="fas fa-link-slash"></i> {{ __('google-sheets::messages.actions.disconnect') }}</button>
    @else
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> {{ __('google-sheets::messages.actions.connect_google_sheets') }}</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>{{ __('google-sheets::messages.storage.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">{{ __('google-sheets::messages.storage.description') }}</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:'DM Sans', sans-serif;font-size:12px;color:var(--c-ink-80);">{{ __('google-sheets::messages.storage.command') }}</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>{{ __('google-sheets::messages.extension.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">{{ __('google-sheets::messages.extension.description') }}</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> {{ __('google-sheets::messages.actions.open_app_page') }}</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> {{ __('google-sheets::messages.actions.browse_apps') }}</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-file-excel" style="color:#0f9d58;"></i><h3>{{ __('google-sheets::messages.connection.title') }}</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      {{ __('google-sheets::messages.connection.description') }}
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> {{ __('google-sheets::messages.actions.connect_now') }}</a>
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-secondary"><i class="fas fa-store"></i> {{ __('google-sheets::messages.actions.open_marketplace') }}</a>
    </div>
  </div>
</div>
@else
<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>{{ __('google-sheets::messages.account.title') }}</h3></div>
      <div class="info-card-body">
        @if($token?->google_avatar_url)
          <div style="text-align:center;margin-bottom:12px;">
            <img src="{{ $token->google_avatar_url }}" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--c-ink-05);" alt="">
          </div>
        @endif
        <div class="info-row"><span class="info-row-label">{{ __('google-sheets::messages.account.name') }}</span><span class="info-row-value">{{ $token?->google_name ?? __('google-sheets::messages.common.dash') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-sheets::messages.account.email') }}</span><span class="info-row-value" style="font-size:12px;">{{ $token?->google_email ?? __('google-sheets::messages.common.dash') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-sheets::messages.account.connected_at') }}</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? __('google-sheets::messages.common.dash') }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('google-sheets::messages.account.last_sync') }}</span><span class="info-row-value" id="gsLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? __('google-sheets::messages.common.never') }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-chart-bar"></i><h3>{{ __('google-sheets::messages.stats.title') }}</h3></div>
      <div class="info-card-body">
        <div class="stat-card" style="margin-bottom:10px;padding:12px;">
          <div class="stat-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-file-excel"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSpreadsheets">0</div>
            <div class="stat-label">{{ __('google-sheets::messages.stats.spreadsheets') }}</div>
          </div>
        </div>
        <div class="stat-card" style="padding:12px;">
          <div class="stat-icon" style="background:#4285f418;color:#4285f4;"><i class="fas fa-table"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSheets">0</div>
            <div class="stat-label">{{ __('google-sheets::messages.stats.sheets_total') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">{{ __('google-sheets::messages.table.spreadsheets') }}</span>
        <span class="table-count" id="gsCount">{{ __('google-sheets::messages.table.count_results', ['count' => 0]) }}</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gsSearchInput" placeholder="{{ __('google-sheets::messages.filters.search_spreadsheet') }}" autocomplete="off">
        </div>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>{{ __('google-sheets::messages.columns.name') }}</th>
            <th>{{ __('google-sheets::messages.columns.created_at') }}</th>
            <th>{{ __('google-sheets::messages.columns.modified_at') }}</th>
            <th>{{ __('google-sheets::messages.columns.visibility') }}</th>
            <th style="text-align:right;padding-right:20px;">{{ __('google-sheets::messages.columns.actions') }}</th>
          </tr>
        </thead>
        <tbody id="gsSpreadsheetsTableBody"></tbody>
      </table>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gsCreateModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-file-excel"></i></div>
      <div><div class="modal-title">{{ __('google-sheets::messages.modal.create_title') }}</div><div class="modal-subtitle">{{ __('google-sheets::messages.modal.create_subtitle') }}</div></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.title') }} <span class="required">*</span></label>
        <input type="text" class="form-control" id="gsSpreadsheetTitle" maxlength="500" placeholder="{{ __('google-sheets::messages.form.title_placeholder') }}">
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.initial_sheets') }} <span class="hint">({{ __('google-sheets::messages.form.comma_separated') }})</span></label>
        <input type="text" class="form-control" id="gsSheetTitles" placeholder="{{ __('google-sheets::messages.form.initial_sheets_placeholder') }}">
        <span class="form-hint">{{ __('google-sheets::messages.form.initial_sheets_hint') }}</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-sheets::messages.actions.cancel') }}</button>
      <button class="btn btn-primary" id="gsSaveSpreadsheetBtn"><i class="fas fa-check"></i> {{ __('google-sheets::messages.actions.create') }}</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gsDataModal">
  <div class="modal modal-xl" style="max-width:92vw;width:92vw;">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-table-cells"></i></div>
      <div>
        <div class="modal-title" id="gsDataModalTitle">{{ __('google-sheets::messages.modal.data_title') }}</div>
        <div class="modal-subtitle">{{ __('google-sheets::messages.modal.data_subtitle') }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body" style="padding:0;">
      <div style="border-bottom:1px solid var(--c-ink-05);padding:10px 20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:var(--surface-1);">
        <div style="font-size:11.5px;font-weight:700;color:var(--c-ink-40);text-transform:uppercase;letter-spacing:.04em;flex-shrink:0;">{{ __('google-sheets::messages.form.tabs') }}</div>
        <div id="gsSheetTabsLoader" style="display:flex;gap:6px;flex-wrap:wrap;flex:1;"></div>
        <div style="display:flex;gap:6px;margin-left:auto;flex-shrink:0;">
          <input type="text" class="form-control" id="gsNewSheetTitle" placeholder="{{ __('google-sheets::messages.form.new_sheet_placeholder') }}" style="width:160px;font-size:12.5px;padding:6px 10px;">
          <button class="btn btn-secondary btn-sm" id="gsAddSheetBtn" title="{{ __('google-sheets::messages.actions.add_sheet') }}"><i class="fas fa-plus"></i></button>
        </div>
      </div>

      <div style="padding:12px 20px;border-bottom:1px solid var(--c-ink-05);display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--surface-0);">
        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
          <label style="font-size:12px;font-weight:600;color:var(--c-ink-40);white-space:nowrap;">{{ __('google-sheets::messages.form.range') }}</label>
          <input type="text" class="form-control gs-range-input" id="gsRangeInput" value="{{ __('google-sheets::messages.common.default_sheet') }}!A1:Z50" placeholder="{{ __('google-sheets::messages.common.default_sheet') }}!A1:Z50" style="max-width:220px;">
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn btn-primary btn-sm" id="gsReadRangeBtn"><i class="fas fa-eye"></i> {{ __('google-sheets::messages.actions.read') }}</button>
          <button class="btn btn-secondary btn-sm" id="gsWriteRangeBtn"><i class="fas fa-pen"></i> {{ __('google-sheets::messages.actions.write') }}</button>
          <button class="btn btn-secondary btn-sm" id="gsAppendRowsBtn"><i class="fas fa-arrow-down"></i> {{ __('google-sheets::messages.actions.append') }}</button>
          <button class="btn btn-ghost btn-sm" id="gsClearRangeBtn" style="color:var(--c-danger);"><i class="fas fa-eraser"></i> {{ __('google-sheets::messages.actions.clear') }}</button>
        </div>
      </div>

      <div id="gsDataTableWrap" style="padding:16px 20px;min-height:200px;">
        <div style="text-align:center;padding:40px;color:var(--c-ink-40);">
          <i class="fas fa-table-cells" style="font-size:28px;margin-bottom:8px;display:block;opacity:.3;"></i>
          <p>{{ __('google-sheets::messages.data.select_sheet_to_read') }}</p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-sheets::messages.actions.close') }}</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gsWriteModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">{{ __('google-sheets::messages.modal.write_title') }}</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.range') }} <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsWriteRange" placeholder="{{ __('google-sheets::messages.common.default_sheet') }}!A1">
        <span class="form-hint">{{ __('google-sheets::messages.form.range_example', ['start' => __('google-sheets::messages.common.default_sheet') . '!A1', 'range' => __('google-sheets::messages.common.default_sheet') . '!B2:D5']) }}</span>
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.data') }} <span class="required">*</span></label>
        <textarea class="form-control" id="gsWriteData" rows="8" placeholder="{{ __('google-sheets::messages.form.write_data_placeholder') }}"></textarea>
        <span class="form-hint">{{ __('google-sheets::messages.form.write_data_hint') }}</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-sheets::messages.actions.cancel') }}</button>
      <button class="btn btn-primary" id="gsSaveWriteBtn"><i class="fas fa-check"></i> {{ __('google-sheets::messages.actions.write') }}</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gsAppendModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">{{ __('google-sheets::messages.modal.append_title') }}</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.range') }} <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsAppendRange" placeholder="{{ __('google-sheets::messages.common.default_sheet') }}!A:A">
        <span class="form-hint">{{ __('google-sheets::messages.form.append_range_hint') }}</span>
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('google-sheets::messages.form.append_data') }} <span class="required">*</span></label>
        <textarea class="form-control" id="gsAppendData" rows="8" placeholder="{{ __('google-sheets::messages.form.append_data_placeholder') }}"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-sheets::messages.actions.cancel') }}</button>
      <button class="btn btn-primary" id="gsSaveAppendBtn"><i class="fas fa-arrow-down"></i> {{ __('google-sheets::messages.actions.append_rows') }}</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GS_ROUTES = {
  connect:            '{{ route('google-sheets.oauth.connect') }}',
  disconnect:         '{{ route('google-sheets.oauth.disconnect') }}',
  spreadsheetsData:   '{{ route('google-sheets.spreadsheets.data') }}',
  createSpreadsheet:  '{{ route('google-sheets.spreadsheets.store') }}',
  stats:              '{{ route('google-sheets.stats') }}',
  spreadsheetBase: @json(rtrim(route('google-sheets.index'), '/') . '/spreadsheets'),
};

window.GS_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

window.GS_I18N = @json(\Illuminate\Support\Facades\Lang::get('google-sheets::messages'));

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleSheetsModule) {
    window.GoogleSheetsModule.boot(window.GS_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('google-sheets::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  if (window.GoogleSheetsModule?.handleFailure) {
    window.GoogleSheetsModule.handleFailure(
      @json(__('google-sheets::messages.common.error')),
      @json(session('error')),
      @json(__('google-sheets::messages.errors.unexpected'))
    );
  } else {
    Toast.error(@json(__('google-sheets::messages.common.error')), @json(session('error')));
  }
  @endif
});
</script>
@endpush
