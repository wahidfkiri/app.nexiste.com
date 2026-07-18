@extends('layouts.global')

@section('title', __('settings.title'))

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('settings.title') }}</h1>
    <p>{{ __('settings.subtitle') }}</p>
  </div>
</div>

@if(!$canManageTenant)
  <section class="info-card">
    <div class="info-card-body">
      <div class="form-error">{{ __('settings.no_permission') }}</div>
    </div>
  </section>
@else
  <form id="globalSettingsForm"
        data-secure-form="1"
        data-secure-ajax="1"
        action="{{ route('settings.global.update') }}"
        method="POST"
        novalidate>
    @csrf
    @method('PUT')

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-building"></i>
        {{ __('settings.sections.company_identity') }}
      </h3>
      <div class="row">
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.company_name') }} <span class="required">*</span></label>
            <input type="text" name="tenant_name" class="form-control @error('tenant_name') is-invalid @enderror" value="{{ old('tenant_name', $tenant->name) }}">
            @error('tenant_name')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.email') }}</label>
            <input type="email" name="tenant_email" class="form-control @error('tenant_email') is-invalid @enderror" value="{{ old('tenant_email', $tenant->email) }}">
            @error('tenant_email')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.phone') }}</label>
            <input type="text" name="tenant_phone" class="form-control @error('tenant_phone') is-invalid @enderror" placeholder="+33612345678" value="{{ old('tenant_phone', $tenant->phone) }}">
            @error('tenant_phone')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.country') }}</label>
            <select name="company_country" class="form-control @error('company_country') is-invalid @enderror">
              <option value="">{{ __('settings.fields.select_placeholder') }}</option>
              @foreach(($countries ?? []) as $country)
                <option value="{{ $country['code'] }}" {{ old('company_country', $settings['company_country'] ?? '') === $country['code'] ? 'selected' : '' }}>
                  {{ $country['name'] }} ({{ $country['code'] }}) {{ $country['dial'] }}
                </option>
              @endforeach
            </select>
            @error('company_country')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.postal_code') }}</label>
            <input type="text" name="company_postal_code" class="form-control @error('company_postal_code') is-invalid @enderror" value="{{ old('company_postal_code', $settings['company_postal_code'] ?? '') }}">
            @error('company_postal_code')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-8">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.city') }}</label>
            <input type="text" name="company_city" class="form-control @error('company_city') is-invalid @enderror" value="{{ old('company_city', $settings['company_city'] ?? '') }}">
            @error('company_city')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.address') }}</label>
            <textarea name="tenant_address" rows="3" class="form-control @error('tenant_address') is-invalid @enderror">{{ old('tenant_address', $tenant->address) }}</textarea>
            @error('tenant_address')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.website') }}</label>
            <input type="url" name="company_website" class="form-control @error('company_website') is-invalid @enderror" placeholder="https://..." value="{{ old('company_website', $settings['company_website'] ?? '') }}">
            @error('company_website')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.company_description') }}</label>
            <textarea name="company_description" rows="3" class="form-control @error('company_description') is-invalid @enderror">{{ old('company_description', $settings['company_description'] ?? '') }}</textarea>
            @error('company_description')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-globe"></i>
        {{ __('settings.sections.regional') }}
      </h3>
      <div class="row">
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.timezone') }} <span class="required">*</span></label>
            <select name="tenant_timezone" class="form-control @error('tenant_timezone') is-invalid @enderror" required>
              @foreach(($timezones ?? []) as $tz)
                <option value="{{ $tz }}" {{ old('tenant_timezone', $tenant->timezone ?? 'Europe/Paris') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
              @endforeach
            </select>
            @error('tenant_timezone')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.currency') }} <span class="required">*</span></label>
            <select name="tenant_currency" class="form-control @error('tenant_currency') is-invalid @enderror" required>
              @foreach(($currencies ?? []) as $code => $label)
                <option value="{{ $code }}" {{ old('tenant_currency', strtoupper($tenant->currency ?? 'EUR')) === $code ? 'selected' : '' }}>
                  {{ $code }} - {{ $label }}
                </option>
              @endforeach
            </select>
            @error('tenant_currency')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('common.language') }} <span class="required">*</span></label>
            <select name="tenant_locale" class="form-control @error('tenant_locale') is-invalid @enderror" required>
              <option value="fr" {{ old('tenant_locale', $tenant->locale ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
              <option value="en" {{ old('tenant_locale', $tenant->locale ?? 'fr') === 'en' ? 'selected' : '' }}>English</option>
              <option value="ar" {{ old('tenant_locale', $tenant->locale ?? 'fr') === 'ar' ? 'selected' : '' }}>العربية</option>
            </select>
            @error('tenant_locale')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-sliders"></i>
        {{ __('settings.sections.crm_config') }}
      </h3>
      <div class="row">
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.invoice_prefix') }}</label>
            <input type="text" name="invoice_prefix" class="form-control @error('invoice_prefix') is-invalid @enderror" placeholder="INV" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}">
            @error('invoice_prefix')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.default_tax') }}</label>
            <input type="number" min="0" max="100" step="0.01" name="default_tax_rate" class="form-control @error('default_tax_rate') is-invalid @enderror" value="{{ old('default_tax_rate', $settings['default_tax_rate'] ?? '20') }}">
            @error('default_tax_rate')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.date_format') }}</label>
            @php($format = old('date_format', $settings['date_format'] ?? 'd/m/Y'))
            <select name="date_format" class="form-control @error('date_format') is-invalid @enderror">
              <option value="d/m/Y" {{ $format === 'd/m/Y' ? 'selected' : '' }}>{{ __('settings.fields.date_format_dmy') }}</option>
              <option value="m/d/Y" {{ $format === 'm/d/Y' ? 'selected' : '' }}>{{ __('settings.fields.date_format_mdy') }}</option>
              <option value="Y-m-d" {{ $format === 'Y-m-d' ? 'selected' : '' }}>{{ __('settings.fields.date_format_iso') }}</option>
            </select>
            @error('date_format')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.open_time') }}</label>
            <input type="time" name="business_hours_start" class="form-control @error('business_hours_start') is-invalid @enderror" value="{{ old('business_hours_start', $settings['business_hours_start'] ?? '09:00') }}">
            @error('business_hours_start')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ __('settings.fields.close_time') }}</label>
            <input type="time" name="business_hours_end" class="form-control @error('business_hours_end') is-invalid @enderror" value="{{ old('business_hours_end', $settings['business_hours_end'] ?? '18:00') }}">
            @error('business_hours_end')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" name="notifications_email" value="1" {{ old('notifications_email', $settings['notifications_email'] ?? '1') == '1' ? 'checked' : '' }}>
              {{ __('settings.fields.notif_email') }}
            </label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" name="notifications_browser" value="1" {{ old('notifications_browser', $settings['notifications_browser'] ?? '1') == '1' ? 'checked' : '' }}>
              {{ __('settings.fields.notif_browser') }}
            </label>
          </div>
        </div>
        <div class="col-12" id="automation-suggestions-settings">
          <div class="form-group" style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:16px 18px;border:1px solid var(--c-ink-05);border-radius:16px;background:var(--surface-0);">
            <div>
              <label class="form-label" style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-wand-magic-sparkles"></i>
                {{ __('settings.suggestions.title') }}
              </label>
              <p style="margin:0;color:var(--c-ink-50);font-size:13px;line-height:1.6;">
                {{ __('settings.suggestions.desc') }}
              </p>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-weight:700;white-space:nowrap;margin-top:2px;">
              <input type="checkbox" name="automation_suggestions_enabled" value="1" {{ old('automation_suggestions_enabled', $settings['automation_suggestions_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
              {{ __('settings.suggestions.activate') }}
            </label>
          </div>
        </div>

        <div class="col-12" id="data-backup-settings">
          @php($selectedBackupProvider = collect($backupProviders ?? [])->firstWhere('ready', true)['slug'] ?? null)
          @php($unreadyBackupProviders = collect($backupProviders ?? [])->filter(fn ($provider) => empty($provider['ready']))->values())
          <div class="data-export-panel">
            <div class="data-export-panel-head">
              <div>
                <label class="form-label" style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                  <i class="fas fa-box-archive"></i>
                  {{ __('settings.backup.title') }}
                </label>
                <p class="data-export-panel-copy">
                  {{ __('settings.backup.desc') }}
                </p>
              </div>
              <span class="data-export-panel-badge">{{ __('settings.backup.badge') }}</span>
            </div>

            <div class="data-export-provider-grid">
              @foreach(($backupProviders ?? []) as $provider)
                @php($isReady = !empty($provider['ready']))
                @php($isInstalled = !empty($provider['installed']))
                @php($isConnected = !empty($provider['connected']))
                <label class="data-export-provider-card {{ $isReady ? 'is-ready' : ($isInstalled ? 'is-warning' : 'is-missing') }}">
                  <input
                    type="radio"
                    name="data_export_provider"
                    value="{{ $provider['slug'] }}"
                    {{ $isReady ? '' : 'disabled' }}
                    {{ $selectedBackupProvider === $provider['slug'] ? 'checked' : '' }}
                  >
                  <div class="data-export-provider-icon">
                    <i class="{{ $provider['icon'] }}"></i>
                  </div>
                  <div class="data-export-provider-body">
                    <div class="data-export-provider-title-row">
                      <strong>{{ $provider['label'] }}</strong>
                      <span class="data-export-provider-state {{ $isReady ? 'is-ready' : ($isInstalled ? 'is-warning' : 'is-missing') }}">
                        {{ $isReady ? __('settings.backup.state_ready') : ($isInstalled ? __('settings.backup.state_reconnect') : __('settings.backup.state_install')) }}
                      </span>
                    </div>
                    <p class="data-export-provider-copy">
                      @if($isReady)
                        {{ __('settings.backup.copy_ready') }}
                      @elseif($isInstalled)
                        {{ __('settings.backup.copy_reconnect') }}
                      @else
                        {{ __('settings.backup.copy_missing') }}
                      @endif
                    </p>
                    @if(!$isReady)
                      <a href="{{ $provider['action_url'] }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
                        <i class="fas {{ $isInstalled ? 'fa-up-right-from-square' : 'fa-puzzle-piece' }}"></i>
                        {{ $isInstalled ? __('settings.backup.open_app', ['provider' => $provider['label']]) : $provider['action_label'] }}
                      </a>
                    @endif
                  </div>
                </label>
              @endforeach
            </div>

            @if($unreadyBackupProviders->isNotEmpty())
              <div class="data-export-connect-strip">
                <div class="data-export-connect-copy">
                  <strong>{{ __('settings.backup.prepare_title') }}</strong>
                  <p>
                    {{ __('settings.backup.prepare_copy') }}
                  </p>
                </div>
                <div class="data-export-connect-actions">
                  @foreach($unreadyBackupProviders as $provider)
                    @php($providerInstalled = !empty($provider['installed']))
                    <a
                      href="{{ $provider['action_url'] }}"
                      class="data-export-connect-link {{ $providerInstalled ? 'is-warning' : 'is-missing' }}"
                      target="_blank"
                      rel="noopener"
                    >
                      <span class="data-export-connect-icon">
                        <i class="{{ $provider['icon'] }}"></i>
                      </span>
                      <span class="data-export-connect-text">
                        <strong>{{ $providerInstalled ? __('settings.backup.connect_provider', ['provider' => $provider['label']]) : __('settings.backup.install_provider', ['provider' => $provider['label']]) }}</strong>
                        <span>{{ $providerInstalled ? __('settings.backup.connect_copy') : __('settings.backup.install_copy') }}</span>
                      </span>
                      <i class="fas fa-up-right-from-square"></i>
                    </a>
                  @endforeach
                </div>
              </div>
            @endif

            <div class="data-export-actions">
              <div class="data-export-actions-copy">
                {{ __('settings.backup.actions_copy') }}
              </div>
              <div class="data-export-actions-buttons">
                <button type="button" class="btn btn-primary" id="startGlobalDataExportBtn" {{ $selectedBackupProvider ? '' : 'disabled' }}>
                  <i class="fas fa-cloud-arrow-up"></i> {{ __('settings.backup.start_btn') }}
                </button>
              </div>
            </div>

            <div
              id="dataExportStatusCard"
              class="data-export-status-card {{ !empty($currentDataExport) ? 'is-visible' : '' }}"
              data-current-export='@json($currentDataExport)'
            >
              <div class="data-export-status-head">
                <div>
                  <div class="data-export-status-kicker">{{ __('settings.backup.tracking_kicker') }}</div>
                  <h4 id="dataExportStatusTitle">{{ __('settings.backup.pending_title') }}</h4>
                  <p id="dataExportStatusSubtitle">{{ __('settings.backup.pending_subtitle') }}</p>
                  <div class="data-export-active-step" id="dataExportActiveStepTitle" style="display:none;"></div>
                </div>
                <div class="data-export-status-meta">
                  <span class="data-export-status-badge" id="dataExportStatusBadge">{{ __('settings.backup.status_pending') }}</span>
                  <span class="data-export-status-provider" id="dataExportStatusProvider">{{ __('settings.backup.no_destination') }}</span>
                </div>
              </div>

              <div class="data-export-progress-wrap">
                <div class="data-export-progress-bar">
                  <span id="dataExportProgressBar"></span>
                </div>
                <div class="data-export-progress-row">
                  <strong id="dataExportProgressPercent">0%</strong>
                  <span id="dataExportCurrentStep">{{ __('settings.backup.no_step') }}</span>
                </div>
              </div>

              <div class="data-export-timeline" id="dataExportTimeline"></div>

              <div class="data-export-columns">
                <div class="data-export-column">
                  <div class="data-export-column-title">
                    <i class="fas fa-wave-square"></i> {{ __('settings.backup.exec_journal') }}
                  </div>
                  <div class="data-export-log-list" id="dataExportLogList"></div>
                </div>
                <div class="data-export-column">
                  <div class="data-export-column-title">
                    <i class="fas fa-shield-halved"></i> {{ __('settings.backup.warnings_result') }}
                  </div>
                  <div class="data-export-warning-list" id="dataExportWarningList"></div>
                  <div class="data-export-result-box" id="dataExportResultBox" style="display:none;">
                    <div class="data-export-result-title">{{ __('settings.backup.archive_available') }}</div>
                    <div class="data-export-result-copy" id="dataExportResultCopy"></div>
                    <div class="data-export-result-actions">
                      <a href="#" target="_blank" rel="noopener" class="btn btn-primary btn-sm" id="dataExportOpenRemoteBtn" style="display:none;">
                        <i class="fas fa-up-right-from-square"></i> {{ __('settings.backup.open_remote') }}
                      </a>
                      <a href="#" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" id="dataExportProviderActionBtn" style="display:none;"></a>
                      <button type="button" class="btn btn-secondary btn-sm" id="dataExportRestartBtn" style="display:none;">
                        <i class="fas fa-rotate-right"></i> {{ __('settings.backup.restart') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="data-export-history">
              <div class="data-export-column-title" style="margin-bottom:8px;">
                <i class="fas fa-clock-rotate-left"></i> {{ __('settings.backup.old_backups') }}
              </div>
              <div class="data-export-history-list" id="dataExportHistoryList">
                @forelse(($dataExportHistory ?? []) as $historyItem)
                  <div class="data-export-history-item" data-export-history-id="{{ $historyItem['id'] }}">
                    <div class="data-export-history-main">
                      <div class="data-export-history-row">
                        <strong>{{ $historyItem['reference_date_label'] ?: __('settings.backup.date_unavailable') }}</strong>
                        <span class="data-export-history-provider">
                          <i class="{{ $historyItem['provider']['icon'] }}"></i>
                          {{ $historyItem['provider']['label'] }}
                        </span>
                      </div>
                      <div class="data-export-history-copy">
                        {{ $historyItem['file_name'] ?: __('settings.backup.archive_zip') }}
                        @if(!empty($historyItem['error_message']))
                          <span> - {{ $historyItem['error_message'] }}</span>
                        @endif
                      </div>
                    </div>
                    <div class="data-export-history-actions">
                      <span class="data-export-provider-state {{ $historyItem['status'] === 'completed' ? 'is-ready' : 'is-warning' }}">
                        {{ $historyItem['status_label'] }}
                      </span>
                      @if(!empty($historyItem['remote_url']))
                        <a href="{{ $historyItem['remote_url'] }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                          <i class="fas fa-up-right-from-square"></i> {{ __('settings.backup.open') }}
                        </a>
                      @endif
                    </div>
                  </div>
                @empty
                  <div class="data-export-history-empty" id="dataExportHistoryEmpty">
                    {{ __('settings.backup.no_history') }}
                  </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    @if(!empty($isSuperAdmin) && !empty($oauthSessionInsights))
      <section class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-user-shield"></i>
          {{ __('settings.sections.oauth_sessions') }}
        </h3>
        <div class="oauth-insights-panel">
          <div class="oauth-insights-copy">
            {{ __('settings.oauth.copy') }}
          </div>

          <div class="oauth-insights-grid">
            @foreach($oauthSessionInsights as $insight)
              <article class="oauth-insight-card">
                <div class="oauth-insight-head">
                  <div>
                    <strong>{{ $insight['label'] }}</strong>
                    <div class="oauth-insight-identity">{{ $insight['connected_identity'] }}</div>
                  </div>
                  <span class="oauth-insight-state is-{{ $insight['status_tone'] }}">{{ $insight['status_label'] }}</span>
                </div>

                <dl class="oauth-insight-meta">
                  <div>
                    <dt>{{ __('settings.oauth.nominal_lifetime') }}</dt>
                    <dd>{{ $insight['nominal_lifetime'] }}</dd>
                  </div>
                  <div>
                    <dt>{{ __('settings.oauth.local_expiry') }}</dt>
                    <dd>{{ $insight['expires_at_label'] }}</dd>
                  </div>
                  <div>
                    <dt>{{ __('settings.oauth.refresh_token') }}</dt>
                    <dd>{{ $insight['refresh_token_label'] }}</dd>
                  </div>
                  <div>
                    <dt>{{ __('settings.oauth.buffer') }}</dt>
                    <dd>{{ $insight['refresh_buffer_label'] }}</dd>
                  </div>
                </dl>

                <div class="oauth-insight-strategy">{{ $insight['strategy'] }}</div>
                <div class="oauth-insight-note">{{ $insight['note'] }}</div>
              </article>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    <div class="form-actions">
      <button type="submit" class="btn btn-primary" id="globalSettingsSaveBtn">
        <i class="fas fa-floppy-disk"></i> {{ __('settings.save') }}
      </button>
    </div>
  </form>
@endif
@endsection

@push('styles')
<style>
  .data-export-panel{display:flex;flex-direction:column;gap:18px;padding:20px 22px;border:1px solid rgba(15,23,42,.08);border-radius:20px;background:#fff;box-shadow:none}
  .data-export-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
  .data-export-panel-copy{margin:0;color:var(--c-ink-60);font-size:13px;line-height:1.7;max-width:860px}
  .data-export-panel-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(37,99,235,.06);color:#1d4ed8;font-size:11px;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
  .data-export-provider-grid{display:flex;flex-direction:column;gap:0;border-top:1px solid rgba(15,23,42,.08);border-bottom:1px solid rgba(15,23,42,.08)}
  .data-export-provider-card{position:relative;display:flex;gap:12px;padding:14px 0;border:none;border-radius:0;background:transparent;transition:background .18s ease}
  .data-export-provider-card + .data-export-provider-card{border-top:1px solid rgba(15,23,42,.08)}
  .data-export-provider-card:hover{background:rgba(248,250,252,.8)}
  .data-export-provider-card input{position:absolute;inset:0;opacity:0;cursor:pointer}
  .data-export-provider-card input:disabled{cursor:not-allowed}
  .data-export-provider-card:has(input:checked){background:rgba(239,246,255,.9)}
  .data-export-provider-icon{width:24px;height:24px;display:flex;align-items:center;justify-content:center;background:transparent;color:#1d4ed8;font-size:16px;flex-shrink:0;margin-top:2px}
  .data-export-provider-body{display:flex;flex-direction:column;gap:8px;min-width:0;flex:1}
  .data-export-provider-title-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .data-export-provider-copy{margin:0;color:var(--c-ink-55);font-size:13px;line-height:1.65}
  .data-export-provider-state{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
  .data-export-provider-state.is-ready{background:rgba(34,197,94,.12);color:#15803d}
  .data-export-provider-state.is-warning{background:rgba(245,158,11,.14);color:#b45309}
  .data-export-provider-state.is-missing{background:rgba(248,113,113,.14);color:#b91c1c}
  .data-export-connect-strip{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:14px 0 0;border-top:1px dashed rgba(15,23,42,.12)}
  .data-export-connect-copy{max-width:480px}
  .data-export-connect-copy strong{display:block;font-size:14px;color:var(--c-ink-90);margin-bottom:6px}
  .data-export-connect-copy p{margin:0;color:var(--c-ink-60);font-size:13px;line-height:1.7}
  .data-export-connect-actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end}
  .data-export-connect-link{display:flex;align-items:center;gap:12px;min-width:260px;max-width:360px;padding:12px 14px;border-radius:14px;background:#fff;border:1px solid rgba(15,23,42,.08);text-decoration:none;color:inherit;transition:border-color .18s ease,background .18s ease}
  .data-export-connect-link:hover{background:#f8fafc}
  .data-export-connect-link.is-warning{border-color:rgba(245,158,11,.28)}
  .data-export-connect-link.is-missing{border-color:rgba(248,113,113,.26)}
  .data-export-connect-icon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.08);color:#1d4ed8;font-size:14px;flex-shrink:0}
  .data-export-connect-text{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}
  .data-export-connect-text strong{font-size:13px;color:var(--c-ink-90)}
  .data-export-connect-text span{font-size:12px;line-height:1.55;color:var(--c-ink-55)}
  .data-export-actions{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:14px 0 0;border-top:1px dashed rgba(15,23,42,.12);border-radius:0;background:transparent;border-left:none;border-right:none;border-bottom:none}
  .data-export-actions-copy{color:var(--c-ink-60);font-size:13px;line-height:1.7;max-width:760px}
  .data-export-status-card{display:none;flex-direction:column;gap:16px;padding:18px 0 0;border-top:1px solid rgba(15,23,42,.08);background:transparent;color:var(--c-ink-90);opacity:1;transition:opacity .28s ease,transform .28s ease}
  .data-export-status-card.is-visible{display:flex}
  .data-export-status-card.is-hiding{opacity:0;transform:translateY(-8px)}
  .data-export-status-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
  .data-export-status-kicker{display:inline-flex;align-items:center;gap:8px;padding:5px 10px;border-radius:999px;background:rgba(37,99,235,.08);color:#1d4ed8;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px}
  .data-export-status-head h4{margin:0 0 4px;font-size:20px;color:var(--c-ink-90)}
  .data-export-active-step{display:none;align-items:center;gap:8px;margin:8px 0 0;padding-left:12px;border-left:3px solid rgba(56,189,248,.72);font-size:14px;font-weight:700;color:var(--c-ink-90)}
  .data-export-active-step.is-visible{display:inline-flex}
  .data-export-status-head p{margin:0;color:var(--c-ink-60);font-size:13px;line-height:1.7;max-width:680px}
  .data-export-status-meta{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
  .data-export-status-badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(59,130,246,.12);color:#1d4ed8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
  .data-export-status-provider{font-size:13px;color:var(--c-ink-60)}
  .data-export-progress-wrap{display:flex;flex-direction:column;gap:10px}
  .data-export-progress-bar{position:relative;height:8px;border-radius:999px;background:rgba(148,163,184,.18);overflow:hidden}
  .data-export-progress-bar span{position:absolute;left:0;top:0;height:100%;width:0;background:linear-gradient(90deg,#38bdf8 0%,#22c55e 100%);border-radius:inherit;transition:width .35s ease}
  .data-export-progress-row{display:flex;align-items:center;justify-content:space-between;gap:18px;font-size:13px;color:var(--c-ink-60)}
  .data-export-progress-row strong{font-size:18px;color:var(--c-ink-90)}
  .data-export-timeline{display:none}
  .data-export-step{padding:10px 0 10px 14px;border-left:3px solid rgba(148,163,184,.24);background:transparent;border-radius:0}
  .data-export-step.is-completed{border-left-color:rgba(34,197,94,.72)}
  .data-export-step.is-running{border-left-color:rgba(56,189,248,.72)}
  .data-export-step.is-failed{border-left-color:rgba(248,113,113,.72)}
  .data-export-step-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:4px}
  .data-export-step-index{width:auto;height:auto;border-radius:0;display:inline-flex;align-items:center;justify-content:center;background:transparent;font-size:12px;font-weight:700;color:var(--c-ink-55)}
  .data-export-step-state{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--c-ink-55)}
  .data-export-step-label{font-size:13px;font-weight:700;color:var(--c-ink-90);margin-bottom:2px}
  .data-export-step-copy{font-size:12px;line-height:1.55;color:var(--c-ink-55)}
  .data-export-columns{display:grid;grid-template-columns:1fr;gap:14px}
  .data-export-column{padding:14px 0 0;background:transparent;border:none;border-top:1px solid rgba(15,23,42,.08);border-radius:0;min-height:0}
  .data-export-column-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--c-ink-90);margin-bottom:10px}
  .data-export-log-list,.data-export-warning-list{display:flex;flex-direction:column;gap:10px}
  .data-export-log-item,.data-export-warning-item{padding:0 0 0 12px;border-radius:0;background:transparent;border:none;border-left:2px solid rgba(148,163,184,.24)}
  .data-export-log-item strong,.data-export-warning-item strong{display:block;font-size:12px;color:var(--c-ink-90);margin-bottom:3px}
  .data-export-log-item span,.data-export-warning-item span{display:block;font-size:12px;line-height:1.6;color:var(--c-ink-60)}
  .data-export-warning-item.is-warning{border-left-color:rgba(245,158,11,.72)}
  .data-export-warning-item.is-error{border-left-color:rgba(248,113,113,.72)}
  .data-export-result-box{margin-top:12px;padding:12px 0 0;border-radius:0;background:transparent;border:none;border-top:1px dashed rgba(15,23,42,.12)}
  .data-export-result-title{font-size:13px;font-weight:700;color:var(--c-ink-90);margin-bottom:6px}
  .data-export-result-copy{font-size:12px;line-height:1.6;color:var(--c-ink-60);margin-bottom:12px}
  .data-export-result-actions{display:flex;flex-wrap:wrap;gap:10px}
  .data-export-history{display:flex;flex-direction:column;gap:10px;padding-top:14px;border-top:1px solid rgba(15,23,42,.08)}
  .data-export-history-list{display:flex;flex-direction:column;gap:10px}
  .data-export-history-item{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid rgba(15,23,42,.08)}
  .data-export-history-item:last-child{border-bottom:none;padding-bottom:0}
  .data-export-history-main{display:flex;flex-direction:column;gap:4px;min-width:0}
  .data-export-history-row{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
  .data-export-history-row strong{font-size:13px;color:var(--c-ink-90)}
  .data-export-history-provider{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--c-ink-55)}
  .data-export-history-copy{font-size:12px;line-height:1.6;color:var(--c-ink-60);word-break:break-word}
  .data-export-history-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
  .data-export-history-empty{font-size:12px;color:var(--c-ink-55);padding:4px 0}
  .oauth-insights-panel{display:flex;flex-direction:column;gap:16px;padding:18px 20px;border:1px solid rgba(15,23,42,.08);border-radius:18px;background:#fff}
  .oauth-insights-copy{font-size:13px;line-height:1.7;color:var(--c-ink-60);max-width:920px}
  .oauth-insights-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .oauth-insight-card{display:flex;flex-direction:column;gap:12px;padding:16px;border:1px solid rgba(15,23,42,.08);border-radius:16px;background:#fcfcfd}
  .oauth-insight-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
  .oauth-insight-head strong{font-size:15px;color:var(--c-ink-90)}
  .oauth-insight-identity{margin-top:4px;font-size:12px;line-height:1.55;color:var(--c-ink-55)}
  .oauth-insight-state{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
  .oauth-insight-state.is-ready{background:rgba(34,197,94,.12);color:#15803d}
  .oauth-insight-state.is-warning{background:rgba(245,158,11,.14);color:#b45309}
  .oauth-insight-state.is-idle{background:rgba(148,163,184,.16);color:#475569}
  .oauth-insight-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px;margin:0}
  .oauth-insight-meta div{display:flex;flex-direction:column;gap:4px;padding-top:10px;border-top:1px dashed rgba(15,23,42,.08)}
  .oauth-insight-meta dt{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--c-ink-45)}
  .oauth-insight-meta dd{margin:0;font-size:13px;line-height:1.55;color:var(--c-ink-90)}
  .oauth-insight-strategy{font-size:12px;line-height:1.65;color:#1d4ed8}
  .oauth-insight-note{font-size:12px;line-height:1.65;color:var(--c-ink-55)}
  @media (max-width: 980px){
    .data-export-panel-head,.data-export-status-head,.data-export-actions,.data-export-connect-strip{flex-direction:column;align-items:flex-start}
    .data-export-status-meta{align-items:flex-start}
    .data-export-columns{grid-template-columns:1fr}
    .data-export-connect-actions{width:100%;justify-content:flex-start}
    .data-export-connect-link{max-width:none;width:100%}
    .data-export-history-item{flex-direction:column;align-items:flex-start}
    .data-export-history-actions{flex-wrap:wrap}
    .oauth-insights-grid,.oauth-insight-meta{grid-template-columns:1fr}
  }
</style>
@endpush

@push('scripts')
<script>
window.GlobalDataExportConfig = {
  startUrl: @json(route('settings.global.exports.start')),
  processUrlTemplate: @json(route('settings.global.exports.process', ['dataExport' => '__ID__'])),
  showUrlTemplate: @json(route('settings.global.exports.show', ['dataExport' => '__ID__'])),
  currentExport: @json($currentDataExport),
  history: @json($dataExportHistory ?? []),
};
</script>
<script src="{{ asset('vendor/client/js/global-settings.js') }}"></script>
@endpush
