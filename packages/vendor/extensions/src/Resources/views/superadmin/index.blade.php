@extends('layouts.global')

@section('title', __('extensions::extensions.superadmin.index.title'))

@section('breadcrumb')
  <span>{{ __('extensions::extensions.common.super_admin') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('extensions::extensions.superadmin.index.heading') }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-puzzle-piece', 'bg' => '#ede9fe', 'color' => '#7c3aed', 'alt' => __('extensions::extensions.common.extensions')])
      <h1 style="margin:0;">{{ __('extensions::extensions.superadmin.index.heading') }}</h1>
    </div>
    <p>{{ __('extensions::extensions.superadmin.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('superadmin.extensions.activations.index') }}" class="btn btn-secondary">
      <i class="fas fa-plug"></i> {{ __('extensions::extensions.common.tenant_activations') }}
    </a>
    <a href="{{ route('superadmin.extensions.export.excel') }}" class="btn btn-secondary">
      <i class="fas fa-file-excel"></i> {{ __('extensions::extensions.actions.export') }}
    </a>
    <a href="{{ route('superadmin.extensions.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('extensions::extensions.actions.new_extension') }}
    </a>
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-puzzle-piece"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotal">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_total') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success);"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiActive">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_active') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning);"><i class="fas fa-star"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiFeatured">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_featured') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent);"><i class="fas fa-gift"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiFree">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_free') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-download"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiInstalls">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_installs') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiRevenue">—</div>
      <div class="stat-label">{{ __('extensions::extensions.superadmin.index.kpi_revenue') }}</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('extensions::extensions.superadmin.index.table_title') }}</span>
    <span class="table-count" id="extCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ __('extensions::extensions.superadmin.index.search_placeholder') }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="category">
      <option value="">{{ __('extensions::extensions.superadmin.index.all_categories') }}</option>
      @foreach($categories as $key => $cat)
        <option value="{{ $key }}">{{ $cat['label'] }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">{{ __('extensions::extensions.superadmin.index.all_statuses') }}</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="pricing_type">
      <option value="">{{ __('extensions::extensions.superadmin.index.all_prices') }}</option>
      @foreach($pricingTypes as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="extTable">
    <thead>
      <tr>
        <th style="width:48px"></th>
        <th data-sort="name" class="sortable">{{ __('extensions::extensions.superadmin.index.column_extension') }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>{{ __('extensions::extensions.superadmin.index.category_label') }}</th>
        <th>{{ __('extensions::extensions.superadmin.index.column_pricing') }}</th>
        <th style="text-align:center" data-sort="installs_count" class="sortable">{{ __('extensions::extensions.superadmin.index.column_installs') }}</th>
        <th style="text-align:center">{{ __('extensions::extensions.superadmin.index.column_featured') }}</th>
        <th>{{ __('extensions::extensions.superadmin.index.column_status') }}</th>
        <th style="text-align:right;padding-right:20px">{{ __('extensions::extensions.superadmin.index.column_actions') }}</th>
      </tr>
    </thead>
    <tbody id="extTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

@endsection

@push('scripts')
<script>
@php
  $superadminIndexI18n = [
      'statusActive' => __('extensions::extensions.status.active'),
      'statusInactive' => __('extensions::extensions.status.inactive'),
      'statusDeprecated' => __('extensions::extensions.status.deprecated'),
      'statusBeta' => __('extensions::extensions.status.beta'),
      'statusComingSoon' => __('extensions::extensions.status.coming_soon'),
      'error' => __('extensions::extensions.common.error'),
      'loadingError' => __('extensions::extensions.common.loading_error'),
      'activeInstalls' => __('extensions::extensions.superadmin.index.active_installs', ['count' => ':count']),
      'featureOn' => __('extensions::extensions.actions.feature_on'),
      'featureOff' => __('extensions::extensions.actions.feature_off'),
      'fromToTotal' => __('extensions::extensions.superadmin.index.from_to_total', ['from' => ':from', 'to' => ':to', 'total' => ':total']),
      'updated' => __('extensions::extensions.superadmin.index.updated'),
      'statusUpdated' => __('extensions::extensions.superadmin.index.status_updated'),
      'deleteTitle' => __('extensions::extensions.superadmin.index.delete_title', ['name' => ':name']),
      'deleteMessage' => __('extensions::extensions.superadmin.index.delete_message'),
      'deleteConfirm' => __('extensions::extensions.common.delete'),
      'deleted' => __('extensions::extensions.superadmin.index.deleted'),
  ];
@endphp
window.EXT_ROUTES = {
  data:  '{{ route("superadmin.extensions.data") }}',
  stats: '{{ route("superadmin.extensions.stats") }}',
};
const EXT_I18N = @json($superadminIndexI18n);
const STATUS_STYLES = {
  active:      { cls:'actif',    label:EXT_I18N.statusActive },
  inactive:    { cls:'inactif',  label:EXT_I18N.statusInactive },
  deprecated:  { cls:'inactif',  label:EXT_I18N.statusDeprecated },
  beta:        { cls:'info',     label:EXT_I18N.statusBeta },
  coming_soon: { cls:'warning',  label:EXT_I18N.statusComingSoon },
};

class ExtTable {
  constructor() {
    this.state = { page:1, search:'', filters:{}, sort:'sort_order', dir:'asc', loading:false };
    this._deb  = null;
    this._bindEvents();
    this.load();
    this.loadStats();
  }

  _bindEvents() {
    document.getElementById('searchInput')?.addEventListener('input', () => {
      clearTimeout(this._deb);
      this._deb = setTimeout(() => {
        this.state.search = document.getElementById('searchInput').value.trim();
        this.state.page = 1; this.load();
      }, 350);
    });
    document.querySelectorAll('[data-filter]').forEach(el => {
      el.addEventListener('change', () => {
        this.state.filters[el.dataset.filter] = el.value;
        this.state.page = 1; this.load();
      });
    });
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state = { ...this.state, search:'', filters:{}, page:1 };
      document.getElementById('searchInput').value = '';
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.load();
    });
    document.querySelectorAll('[data-sort]').forEach(th => {
      th.addEventListener('click', () => {
        const col = th.dataset.sort;
        if (this.state.sort === col) this.state.dir = this.state.dir === 'asc' ? 'desc' : 'asc';
        else { this.state.sort = col; this.state.dir = 'asc'; }
        this.load();
      });
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById('extTableBody');
    if (tbody) tbody.innerHTML = Array.from({length:5},()=>`<tr>${Array.from({length:8},()=>`<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`).join('');

    const params = { page:this.state.page, per_page:20, search:this.state.search, sort:this.state.sort, dir:this.state.dir, ...this.state.filters };
    const { ok, data } = await Http.get(window.EXT_ROUTES.data, params);
    this.state.loading = false;
    if (!ok) { Toast.error(EXT_I18N.error, EXT_I18N.loadingError); return; }
    this._render(data.data || []);
    this._renderPagination(data);
    const cnt = document.getElementById('extCount');
    if (cnt) cnt.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(window.EXT_ROUTES.stats);
    if (!ok || !data.data) return;
    const s = data.data;
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpiTotal',    s.total || 0);
    set('kpiActive',   s.active || 0);
    set('kpiFeatured', s.featured || 0);
    set('kpiFree',     s.free || 0);
    set('kpiInstalls', s.total_installs || 0);
    set('kpiRevenue',  new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR',maximumFractionDigits:0}).format(s.total_revenue || 0));
  }

  _render(rows) {
    const tbody = document.getElementById('extTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty">
        <div class="table-empty-icon"><i class="fas fa-puzzle-piece"></i></div>
        <h3>{{ __('extensions::extensions.superadmin.index.empty_title') }}</h3>
        <p>{{ __('extensions::extensions.superadmin.index.empty_description') }}</p>
        <a href="{{ route('superadmin.extensions.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('extensions::extensions.actions.create_extension') }}</a>
      </div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map(e => this._row(e)).join('');
  }

  _row(e) {
    const color     = e.category_color || '#64748b';
    const iconSrc   = this._iconSource(e.icon_url || e.icon);
    const iconClass = this._iconClass(e.icon || e.category_icon || 'fa-puzzle-piece');
    const iconHtml  = iconSrc
      ? `<img src="${this._esc(iconSrc)}" style="width:28px;height:28px;object-fit:contain;" alt="">`
      : `<i class="${iconClass}" style="color:${color};font-size:18px;"></i>`;
    const st        = STATUS_STYLES[e.status] || { cls:'secondary', label: e.status };
    const priceHtml = e.is_free
      ? `<span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">{{ __('extensions::extensions.common.free') }}</span>`
      : `<span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">${this._esc(e.pricing_label)}</span>`;

    return `
    <tr data-id="${e.id}">
      <td style="width:48px;padding-left:16px;">
        <div style="width:38px;height:38px;border-radius:10px;background:${color}18;display:flex;align-items:center;justify-content:center;">${iconHtml}</div>
      </td>
      <td>
        <div style="font-weight:var(--fw-semi);color:var(--c-ink);display:flex;align-items:center;gap:7px;">
          ${this._esc(e.name)}
          ${e.is_official ? `<span style="background:#f3e8ff;color:#7c3aed;padding:2px 6px;border-radius:99px;font-size:9px;font-weight:700;"><i class="fas fa-certificate" style="font-size:8px;"></i> {{ __('extensions::extensions.superadmin.index.official_badge') }}</span>` : ''}
          ${e.is_new ? `<span style="background:#dbeafe;color:#1d4ed8;padding:2px 6px;border-radius:99px;font-size:9px;font-weight:700;">{{ __('extensions::extensions.superadmin.index.new_badge') }}</span>` : ''}
        </div>
        <div style="font-size:11.5px;color:var(--c-ink-40);">v${this._esc(e.version || '1.0.0')} · ${this._esc(e.slug)}</div>
      </td>
      <td>
        <span style="background:${color}18;color:${color};padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          <i class="fas ${e.category_icon}" style="font-size:10px;margin-right:4px;"></i>${this._esc(e.category_label)}
        </span>
      </td>
      <td>${priceHtml}</td>
      <td style="text-align:center;">
        <span style="font-weight:var(--fw-semi);color:var(--c-ink);font-size:14px;">${e.installs || 0}</span>
        <div style="font-size:11px;color:var(--c-ink-40);">${(e.active_installs || 0) + ' ' + EXT_I18N.activeInstalls.replace(':count', e.active_installs || 0).replace(/^\d+\s/, '')}</div>
      </td>
      <td style="text-align:center;">
        <button onclick="ExtTable.toggleFeatured('${this._attrJs(e.featured_url)}', ${e.id})" title="${e.is_featured ? EXT_I18N.featureOff : EXT_I18N.featureOn}"
                style="background:none;border:none;cursor:pointer;font-size:18px;color:${e.is_featured ? '#f59e0b' : 'var(--c-ink-10)'};transition:color .2s;"
                id="featBtn-${e.id}">
          <i class="fas fa-star"></i>
        </button>
      </td>
      <td>
        <span class="badge badge-${st.cls}">${st.label}</span>
      </td>
      <td>
        <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
          <a href="${this._url(e.show_url)}" class="btn-icon" title="{{ __('extensions::extensions.actions.show') }}"><i class="fas fa-eye"></i></a>
          <a href="${this._url(e.edit_url)}" class="btn-icon" title="{{ __('extensions::extensions.common.edit') }}"><i class="fas fa-pen"></i></a>
          <button class="btn-icon" onclick="ExtTable.toggleStatus('${this._attrJs(e.status_url)}')" title="{{ __('extensions::extensions.actions.toggle_status') }}">
            <i class="fas fa-${e.status === 'active' ? 'pause' : 'play'}"></i>
          </button>
          <button class="btn-icon danger" onclick="ExtTable.deleteExt('${this._attrJs(e.delete_url)}','${this._attrJs(e.name)}')" title="{{ __('extensions::extensions.common.delete') }}">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = EXT_I18N.fromToTotal.replace(':from', from || 0).replace(':to', to || 0).replace(':total', total || 0);
    const pages = [];
    for (let i = Math.max(1,current_page-2); i <= Math.min(last_page||1,current_page+2); i++) pages.push(i);
    wrap.innerHTML = `
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._extTable?.goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map(p=>`<button class="page-btn ${p===current_page?'active':''}" onclick="window._extTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._extTable?.goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(p) { this.state.page = p; this.load(); window.scrollTo({top:0,behavior:'smooth'}); }
  _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
  _url(s) { return this._esc(s || '#'); }
  _js(s) {
    return String(s ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\r/g, '').replace(/\n/g, '\\n');
  }
  _attrJs(s) { return this._esc(this._js(s)); }
  _iconClass(value, fallback = 'fas fa-puzzle-piece') {
    const raw = String(value || '').trim();
    if (!raw) return fallback;
    const clean = raw.replace(/[^a-zA-Z0-9_\-\s]/g, '').replace(/\s+/g, ' ').trim();
    if (!clean) return fallback;

    const tokens = clean.split(' ');
    const hasGlyph = tokens.some((t) => /^fa-[a-z0-9-]+$/i.test(t));
    const hasFamily = tokens.some((t) => /^(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)$/i.test(t));

    if (!hasGlyph) return fallback;
    if (!hasFamily) return `fas ${clean}`;

    return clean;
  }
  _iconSource(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^(data:|https?:\/\/|\/\/)/i.test(raw)) return raw;
    if (/^(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)(\s|$)/i.test(raw)) return '';
    if (/(^|\s)fa-[a-z0-9-]+(\s|$)/i.test(raw)) return '';
    if (raw.startsWith('/storage/')) return raw;
    if (raw.startsWith('storage/')) return `/${raw}`;
    if (raw.startsWith('/')) return raw;
    if (/\.(png|svg|jpe?g|gif|webp|avif|ico)(\?.*)?$/i.test(raw)) return `/storage/${raw.replace(/^\/+/, '')}`;
    return '';
  }

  static async toggleFeatured(url, id) {
    const { ok, data } = await Http.post(url, {});
    if (ok) {
      Toast.success(EXT_I18N.updated, data.message);
      const btn = document.getElementById(`featBtn-${id}`);
      if (btn) btn.style.color = data.value ? '#f59e0b' : 'var(--c-ink-10)';
    } else Toast.error(EXT_I18N.error, data.message);
  }

  static async toggleStatus(url) {
    const { ok, data } = await Http.post(url, {});
    if (ok) { Toast.success(EXT_I18N.statusUpdated, data.message); window._extTable?.load(); }
    else Toast.error(EXT_I18N.error, data.message);
  }

  static async deleteExt(url, name) {
    Modal.confirm({
      title: EXT_I18N.deleteTitle.replace(':name', name),
      message: EXT_I18N.deleteMessage,
      confirmText: EXT_I18N.deleteConfirm,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(url);
        if (ok) { Toast.success(EXT_I18N.deleted, data.message); window._extTable?.load(); window._extTable?.loadStats(); }
        else Toast.error(EXT_I18N.error, data.message);
      }
    });
  }
}

window._extTable = new ExtTable();
</script>
@endpush
