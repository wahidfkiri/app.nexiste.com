@extends('layouts.global')

@section('title', __('extensions::extensions.marketplace.index.title'))

@section('breadcrumb')
  <span>{{ __('extensions::extensions.common.workspace') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('extensions::extensions.common.applications') }}</span>
@endsection

@section('content')
@php
  $marketplaceAdmin = auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('super-admin'));
@endphp

{{-- Page Hero --}}
<div style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f2044 100%);border-radius:var(--r-2xl);padding:40px 48px;margin-bottom:28px;position:relative;overflow:hidden;">
  {{-- Déco --}}
  <div style="position:absolute;top:-40px;right:-40px;width:280px;height:280px;background:radial-gradient(circle,rgba(37,99,235,.25),transparent 70%);border-radius:50%;"></div>
  <div style="position:absolute;bottom:-60px;left:20%;width:200px;height:200px;background:radial-gradient(circle,rgba(139,92,246,.15),transparent 70%);border-radius:50%;"></div>

  <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
    <div>
      <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);padding:5px 14px;border-radius:99px;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:16px;">
        <i class="fas fa-store" style="color:#60a5fa;"></i>
        {{ __('extensions::extensions.marketplace.index.hero_badge') }}
      </div>
      <div class="page-title-heading" style="margin-bottom:8px;">
        @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-store', 'bg' => 'rgba(255,255,255,.12)', 'color' => '#60a5fa', 'alt' => __('extensions::extensions.common.marketplace')])
        <h1 style="font-size:2.2rem;font-weight:800;color:#fff;margin:0;line-height:1.2;">{{ __('extensions::extensions.marketplace.index.hero_title') }}</h1>
      </div>
      <p style="color:rgba(255,255,255,.6);font-size:15px;max-width:480px;margin:0;">
        {{ __('extensions::extensions.marketplace.index.hero_description') }}
      </p>
    </div>
    <div style="display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end;">
      @if($marketplaceAdmin)
        <a href="{{ route('superadmin.extensions.index') }}" class="btn btn-secondary" style="border-color:rgba(255,255,255,.18);color:#fff;background:rgba(255,255,255,.06);">
          <i class="fas fa-sliders-h"></i> {{ __('extensions::extensions.common.marketplace_settings') }}
        </a>
      @endif
      <a href="{{ route('marketplace.my-apps') }}" class="btn btn-secondary" style="border-color:rgba(255,255,255,.2);color:rgba(255,255,255,.8);background:rgba(255,255,255,.08);">
        <i class="fas fa-th-list"></i> {{ __('extensions::extensions.common.my_apps') }}
        @if($myAppsCount > 0)
          <span style="background:var(--c-accent);color:#fff;padding:1px 7px;border-radius:99px;font-size:11px;margin-left:4px;">{{ $myAppsCount }}</span>
        @endif
      </a>
    </div>
  </div>

  {{-- Stats quick --}}
  <div style="position:relative;z-index:1;display:flex;gap:24px;margin-top:28px;flex-wrap:wrap;">
    @php
      $heroStats = [
        ['icon'=>'fa-puzzle-piece','value'=>'50+','label'=>__('extensions::extensions.marketplace.index.hero_stats.apps')],
        ['icon'=>'fa-gift','value'=>__('extensions::extensions.common.free'),'label'=>__('extensions::extensions.marketplace.index.hero_stats.free')],
        ['icon'=>'fa-bolt','value'=>'1-clic','label'=>__('extensions::extensions.marketplace.index.hero_stats.install')],
        ['icon'=>'fa-shield-alt','value'=>'Sécurisé','label'=>__('extensions::extensions.marketplace.index.hero_stats.secure')],
      ];
    @endphp
    @foreach($heroStats as $s)
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:36px;height:36px;background:rgba(255,255,255,.08);border-radius:10px;display:flex;align-items:center;justify-content:center;">
        <i class="fas {{ $s['icon'] }}" style="color:#60a5fa;font-size:14px;"></i>
      </div>
      <div>
        <div style="color:#fff;font-weight:700;font-size:14px;">{{ $s['value'] }}</div>
        <div style="color:rgba(255,255,255,.45);font-size:11px;">{{ $s['label'] }}</div>
      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- Search + Filters --}}
<div style="background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-xl);padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
  {{-- Search --}}
  <div style="position:relative;flex:1;min-width:200px;">
    <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--c-ink-20);font-size:13px;"></i>
    <input type="text" id="searchInput" placeholder="{{ __('extensions::extensions.common.search_app_placeholder') }}"
           style="width:100%;padding:10px 14px 10px 38px;border:1.5px solid var(--c-ink-10);border-radius:var(--r-md);font-size:14px;background:var(--surface-1);outline:none;transition:border-color .2s;"
           onfocus="this.style.borderColor='var(--c-accent)'" onblur="this.style.borderColor='var(--c-ink-10)'">
  </div>

  {{-- Category pills --}}
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <button class="mkt-cat-btn active" data-category="all" onclick="setCategory('all',this)">
      Toutes
    </button>
    @foreach($categories as $key => $cat)
    <button class="mkt-cat-btn" data-category="{{ $key }}" onclick="setCategory('{{ $key }}',this)"
            style="--cat-color:{{ $cat['color'] }};">
      <i class="fas {{ $cat['icon'] }}" style="font-size:11px;"></i>
      {{ $cat['label'] }}
    </button>
    @endforeach
  </div>

  {{-- Pricing filter --}}
  <select id="pricingFilter" class="filter-select" onchange="applyFilters()">
    <option value="">{{ __('extensions::extensions.marketplace.index.pricing_all') }}</option>
    <option value="free">{{ __('extensions::extensions.marketplace.index.pricing_free') }}</option>
    <option value="paid">{{ __('extensions::extensions.marketplace.index.pricing_paid') }}</option>
  </select>

  {{-- Sort --}}
  <select id="sortFilter" class="filter-select" onchange="applyFilters()">
    <option value="sort_order">{{ __('extensions::extensions.marketplace.index.sort_recommended') }}</option>
    <option value="installs_count">{{ __('extensions::extensions.marketplace.index.sort_popular') }}</option>
    <option value="rating">{{ __('extensions::extensions.marketplace.index.sort_best_rated') }}</option>
    <option value="created_at">{{ __('extensions::extensions.marketplace.index.sort_recent') }}</option>
    <option value="name">{{ __('extensions::extensions.marketplace.index.sort_az') }}</option>
  </select>

  <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ __('extensions::extensions.common.cancel') }}">
    <i class="fas fa-rotate-left"></i>
  </button>
</div>

{{-- Résultats counter --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
  <div style="font-size:13.5px;color:var(--c-ink-60);">
    <span id="appsCount" style="font-weight:var(--fw-semi);color:var(--c-ink);">{{ __('extensions::extensions.common.none_short') }}</span> {{ __('extensions::extensions.marketplace.index.found') }}
  </div>
  {{-- Vue switcher --}}
  <div style="display:flex;gap:4px;background:var(--surface-1);padding:4px;border-radius:var(--r-sm);">
    <button id="gridViewBtn" onclick="setView('grid')" style="padding:5px 8px;border:none;border-radius:6px;background:var(--c-accent);color:#fff;cursor:pointer;transition:all .2s;" title="{{ __('extensions::extensions.marketplace.index.grid') }}">
      <i class="fas fa-th-large" style="font-size:12px;"></i>
    </button>
    <button id="listViewBtn" onclick="setView('list')" style="padding:5px 8px;border:none;border-radius:6px;background:transparent;color:var(--c-ink-40);cursor:pointer;transition:all .2s;" title="{{ __('extensions::extensions.marketplace.index.list') }}">
      <i class="fas fa-list" style="font-size:12px;"></i>
    </button>
  </div>
</div>

{{-- Apps Grid --}}
<div id="appsContainer">
  <div id="appsGrid" class="marketplace-grid">
    {{-- Skeletons --}}
    @for($i = 0; $i < 12; $i++)
    <div class="app-card-skeleton">
      <div class="skeleton" style="width:56px;height:56px;border-radius:14px;margin-bottom:12px;"></div>
      <div class="skeleton" style="height:16px;width:60%;border-radius:4px;margin-bottom:8px;"></div>
      <div class="skeleton" style="height:12px;width:90%;border-radius:4px;margin-bottom:6px;"></div>
      <div class="skeleton" style="height:12px;width:70%;border-radius:4px;margin-bottom:16px;"></div>
      <div class="skeleton" style="height:32px;border-radius:99px;"></div>
    </div>
    @endfor
  </div>
</div>

{{-- Pagination --}}
<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:32px;" id="paginationWrapper"></div>

{{-- Modal détail app --}}
<div class="modal-overlay" id="appModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:14px;" id="modalHeaderContent"></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body" id="modalBody" style="padding:0;max-height:70vh;overflow-y:auto;"></div>
    <div class="modal-footer" id="modalFooter"></div>
  </div>
</div>

@endsection

@push('styles')
<style>
.mkt-cat-btn {
  padding:8px 16px;
  background:var(--surface-1);
  border:1.5px solid var(--c-ink-10);
  border-radius:99px;
  font-size:12.5px;
  font-weight:var(--fw-medium);
  color:var(--c-ink-60);
  cursor:pointer;
  transition:all .2s;
  display:inline-flex;
  align-items:center;
  gap:6px;
}
.mkt-cat-btn:hover {
  border-color:var(--cat-color, var(--c-accent));
  color:var(--cat-color, var(--c-accent));
  background:color-mix(in srgb, var(--cat-color, var(--c-accent)) 8%, transparent);
}
.mkt-cat-btn.active {
  background:var(--cat-color, var(--c-accent));
  border-color:var(--cat-color, var(--c-accent));
  color:#fff;
  box-shadow:0 4px 12px color-mix(in srgb, var(--cat-color, var(--c-accent)) 35%, transparent);
}

/* Grille */
.marketplace-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
  gap:20px;
}
.marketplace-list {
  display:flex;
  flex-direction:column;
  gap:12px;
}

/* App card (grille) */
.app-card {
  background:var(--surface-0);
  border:1px solid var(--c-ink-05);
  border-radius:var(--r-xl);
  padding:22px;
  cursor:pointer;
  transition:all .25s;
  position:relative;
  overflow:hidden;
}
.app-card:hover {
  transform:translateY(-4px);
  box-shadow:0 20px 40px rgba(15,23,42,.1);
  border-color:var(--c-ink-10);
}
.app-card::before {
  content:'';
  position:absolute;
  top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--app-color,#2563eb),color-mix(in srgb,var(--app-color,#2563eb) 60%,#8b5cf6));
  opacity:0;
  transition:opacity .25s;
}
.app-card:hover::before { opacity:1; }

.app-icon-wrap {
  width:56px;height:56px;
  border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:24px;
  margin-bottom:14px;
  flex-shrink:0;
}

.app-badge-pill {
  padding:3px 8px;border-radius:99px;font-size:10px;font-weight:700;
  letter-spacing:.03em;
}
.app-card-title { font-size:15px;font-weight:700;color:var(--c-ink);margin-bottom:4px; }
.app-card-desc  { font-size:12.5px;color:var(--c-ink-40);line-height:1.5;margin-bottom:14px;
                  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.app-card-footer { display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:14px; }
.app-card-meta   { display:flex;align-items:center;gap:12px;font-size:11.5px;color:var(--c-ink-40); }

/* Liste mode */
.app-list-item {
  background:var(--surface-0);
  border:1px solid var(--c-ink-05);
  border-radius:var(--r-lg);
  padding:16px 20px;
  display:flex;align-items:center;gap:16px;
  cursor:pointer;
  transition:all .2s;
}
.app-list-item:hover { box-shadow:var(--shadow-md); transform:translateX(2px); }

/* Activated badge */
.activated-ring {
  position:absolute;inset:0;
  border-radius:var(--r-xl);
  border:2px solid var(--c-success);
  pointer-events:none;
  opacity:.5;
}

.app-card-skeleton { padding:22px;background:var(--surface-0);border-radius:var(--r-xl);border:1px solid var(--c-ink-05); }
</style>
@endpush

@push('scripts')
<script>
window.MKT_ROUTES = {
  data:       @json(route('marketplace.data')),
  stats:      @json(route('marketplace.stats')),
  activate:   @json(route('marketplace.activate', ['slug' => '__SLUG__'])),
  deactivate: @json(route('marketplace.deactivate', ['slug' => '__SLUG__'])),
  show:       @json(route('marketplace.show', ['slug' => '__SLUG__'])),
};

const CATEGORIES = @json($categories);

@php
  $marketplaceI18n = [
      'error' => __('extensions::extensions.common.error'),
      'loadError' => __('extensions::extensions.marketplace.index.load_error'),
      'emptyTitle' => __('extensions::extensions.marketplace.index.empty_title'),
      'emptyDescription' => __('extensions::extensions.marketplace.index.empty_description'),
      'activatedBadge' => __('extensions::extensions.marketplace.index.activated_badge'),
      'trialBadge' => __('extensions::extensions.marketplace.index.trial_badge'),
      'newBadge' => __('extensions::extensions.marketplace.index.new_badge'),
      'officialBadge' => __('extensions::extensions.marketplace.index.official_badge'),
      'installButton' => __('extensions::extensions.marketplace.index.install_button'),
      'tryButton' => __('extensions::extensions.marketplace.index.try_button'),
      'freeLabel' => __('extensions::extensions.marketplace.index.free_label'),
      'activateSuccess' => __('extensions::extensions.marketplace.index.activate_success'),
      'deactivateSuccess' => __('extensions::extensions.marketplace.index.deactivate_success'),
      'activateTrialTitle' => __('extensions::extensions.marketplace.index.delete_modal_activate_trial', ['name' => ':name']),
      'activateFreeTitle' => __('extensions::extensions.marketplace.index.delete_modal_activate_free', ['name' => ':name']),
      'activatePaidTitle' => __('extensions::extensions.marketplace.index.delete_modal_activate_paid', ['name' => ':name']),
      'activateMessageTrial' => __('extensions::extensions.marketplace.index.activate_message_trial'),
      'activateMessageDefault' => __('extensions::extensions.marketplace.index.activate_message_default'),
      'activateConfirmTrial' => __('extensions::extensions.marketplace.index.activate_confirm_trial'),
      'activateConfirmInstall' => __('extensions::extensions.marketplace.index.activate_confirm_install'),
      'deactivateTitle' => __('extensions::extensions.marketplace.index.deactivate_title', ['name' => ':name']),
      'deactivateMessage' => __('extensions::extensions.marketplace.index.deactivate_message'),
      'deactivateConfirm' => __('extensions::extensions.marketplace.index.deactivate_confirm'),
  ];
@endphp
const MKT_I18N = @json($marketplaceI18n);

let state = { page: 1, category: 'all', search: '', pricing: '', sort: 'sort_order', view: 'grid', loading: false };
let debounce;

/* ── View switcher ─────────────────────────────────────────────────────── */
function setView(v) {
  state.view = v;
  const grid = document.getElementById('appsGrid');
  grid.className = v === 'grid' ? 'marketplace-grid' : 'marketplace-list';
  document.getElementById('gridViewBtn').style.background = v === 'grid' ? 'var(--c-accent)' : 'transparent';
  document.getElementById('gridViewBtn').style.color      = v === 'grid' ? '#fff' : 'var(--c-ink-40)';
  document.getElementById('listViewBtn').style.background = v === 'list' ? 'var(--c-accent)' : 'transparent';
  document.getElementById('listViewBtn').style.color      = v === 'list' ? '#fff' : 'var(--c-ink-40)';
}

/* ── Category ──────────────────────────────────────────────────────────── */
function setCategory(cat, btn) {
  state.category = cat;
  state.page     = 1;
  document.querySelectorAll('.mkt-cat-btn').forEach(b => {
    b.classList.remove('active');
    b.style.setProperty('--cat-color', b.dataset.category !== 'all'
      ? (CATEGORIES[b.dataset.category]?.color || 'var(--c-accent)')
      : 'var(--c-accent)');
  });
  btn.classList.add('active');
  loadApps();
}

/* ── Filters ───────────────────────────────────────────────────────────── */
function applyFilters() {
  state.pricing = document.getElementById('pricingFilter').value;
  state.sort    = document.getElementById('sortFilter').value;
  state.page    = 1;
  loadApps();
}

document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(debounce);
  debounce = setTimeout(() => { state.search = this.value.trim(); state.page = 1; loadApps(); }, 350);
});

document.getElementById('resetFilters').addEventListener('click', () => {
  state = { ...state, category: 'all', search: '', pricing: '', sort: 'sort_order', page: 1 };
  document.getElementById('searchInput').value = '';
  document.getElementById('pricingFilter').value = '';
  document.getElementById('sortFilter').value = 'sort_order';
  document.querySelectorAll('.mkt-cat-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('[data-category="all"]').classList.add('active');
  loadApps();
});

/* ── Load apps ─────────────────────────────────────────────────────────── */
async function loadApps() {
  if (state.loading) return;
  state.loading = true;
  showSkeletons();

  const params = new URLSearchParams({
    page: state.page, per_page: 12, search: state.search,
    category: state.category === 'all' ? '' : state.category,
    pricing_type: state.pricing, sort: state.sort,
  });

  const { ok, data } = await Http.get(window.MKT_ROUTES.data + '?' + params);
  state.loading = false;

  if (!ok) { Toast.error(MKT_I18N.error, MKT_I18N.loadError); return; }

  renderApps(data.data || []);
  renderPagination(data);
  document.getElementById('appsCount').textContent = data.total || 0;
}

/* ── Render ────────────────────────────────────────────────────────────── */
function showSkeletons() {
  const grid = document.getElementById('appsGrid');
  grid.innerHTML = Array.from({length: 12}, () => `
    <div class="app-card-skeleton">
      <div class="skeleton" style="width:56px;height:56px;border-radius:14px;margin-bottom:12px;"></div>
      <div class="skeleton" style="height:16px;width:60%;border-radius:4px;margin-bottom:8px;"></div>
      <div class="skeleton" style="height:12px;width:90%;border-radius:4px;margin-bottom:6px;"></div>
      <div class="skeleton" style="height:12px;width:70%;border-radius:4px;margin-bottom:16px;"></div>
      <div class="skeleton" style="height:36px;border-radius:99px;"></div>
    </div>`).join('');
}

function renderApps(apps) {
  const grid = document.getElementById('appsGrid');
  if (!apps.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
      <div style="width:64px;height:64px;background:var(--surface-2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--c-ink-20);"><i class="fas fa-search"></i></div>
      <h3 style="color:var(--c-ink);margin-bottom:8px;">${MKT_I18N.emptyTitle}</h3>
      <p style="color:var(--c-ink-40);">${MKT_I18N.emptyDescription}</p>
    </div>`;
    return;
  }
  if (state.view === 'grid') {
    grid.innerHTML = apps.map(a => renderCardGrid(a)).join('');
  } else {
    grid.innerHTML = apps.map(a => renderCardList(a)).join('');
  }
}

function renderCardGrid(a) {
  const color      = a.category_color || '#2563eb';
  const iconBg     = a.icon_bg_color || `${color}18`;
  const categoryIconClass = _iconClass(a.category_icon || 'fa-puzzle-piece');
  const appIconClass = _iconClass(a.icon, categoryIconClass);
  const iconSrc = _iconSource(a.icon_url || a.icon);
  const slugArg = _attrJs(a.slug || '');
  const nameArg = _attrJs(a.name || '');
  const iconUrlArg = _attrJs(iconSrc);
  const iconClassArg = _attrJs(appIconClass);
  const colorArg = _attrJs(color);
  const iconHtml   = iconSrc
    ? `<img src="${iconSrc}" style="width:32px;height:32px;object-fit:contain;" alt="${_esc(a.name)}">`
    : `<i class="${appIconClass}" style="color:white;font-size:24px;"></i>`;
  const priceHtml  = a.is_free
    ? `<span style="background:#dcfce7;color:#15803d;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700;">${MKT_I18N.freeLabel}</span>`
    : `<span style="background:var(--c-accent-lt);color:var(--c-accent);padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700;">${_esc(a.pricing_label)}</span>`;
  const activeRing  = a.is_activated ? '<div class="activated-ring"></div>' : '';
  const activeBadge = a.is_activated
    ? `<span style="background:#dcfce7;color:#15803d;padding:3px 8px;border-radius:99px;font-size:10px;font-weight:700;"><i class="fas fa-check" style="margin-right:3px;font-size:9px;"></i>${MKT_I18N.activatedBadge}</span>`
    : '';
  const trialBadge = a.has_trial && !a.is_activated
    ? `<span class="app-badge-pill" style="background:#fef3c7;color:#92400e;">${MKT_I18N.trialBadge}</span>`
    : '';
  const newBadge   = a.is_new
    ? `<span class="app-badge-pill" style="background:#dbeafe;color:#1d4ed8;">${MKT_I18N.newBadge}</span>`
    : '';
  const offBadge   = a.is_official
    ? `<span class="app-badge-pill" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-certificate" style="font-size:9px;margin-right:2px;"></i>${MKT_I18N.officialBadge}</span>`
    : '';

  const actionBtn = a.is_activated
    ? `<button class="btn btn-secondary btn-sm" onclick="event.stopPropagation();deactivateApp(${slugArg},${nameArg},${iconUrlArg},${iconClassArg},${colorArg})">
         <i class="fas fa-plug-circle-xmark"></i> ${MKT_I18N.deactivateConfirm}
       </button>`
    : `<button class="btn btn-primary btn-sm" onclick="event.stopPropagation();activateApp(${slugArg},${nameArg},${a.is_free},${a.has_trial},${iconUrlArg},${iconClassArg},${colorArg})">
         <i class="fas fa-plug"></i> ${a.has_trial ? MKT_I18N.tryButton : MKT_I18N.installButton}
       </button>`;

  return `
  <div class="app-card" style="--app-color:${color};" onclick="openAppModal(${slugArg})">
    ${activeRing}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
      <div class="app-icon-wrap" style="background:${iconBg};">
        ${iconHtml}
      </div>
      <div style="display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;">
        ${activeBadge}${newBadge}${offBadge}${trialBadge}
      </div>
    </div>

    <div class="app-card-title">${_esc(a.name)}</div>
    <div class="app-card-desc">${_esc(a.tagline || a.description || '')}</div>

    <div class="app-card-meta">
      <span><i class="fas fa-download" style="font-size:10px;margin-right:3px;"></i>${a.installs_count || 0}</span>
      ${a.rating > 0 ? `<span><i class="fas fa-star" style="color:#f59e0b;font-size:10px;margin-right:3px;"></i>${a.rating}</span>` : ''}
      <span style="background:${color}18;color:${color};padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:600;">
        <i class="${categoryIconClass}" style="font-size:9px;margin-right:3px;"></i>${_esc(a.category_label)}
      </span>
    </div>

    <div class="app-card-footer">
      ${priceHtml}
      ${actionBtn}
    </div>
  </div>`;
}

function renderCardList(a) {
  const color = a.category_color || '#2563eb';
  const iconBg = a.icon_bg_color || `${color}18`;
  const categoryIconClass = _iconClass(a.category_icon || 'fa-puzzle-piece');
  const appIconClass = _iconClass(a.icon, categoryIconClass);
  const iconSrc = _iconSource(a.icon_url || a.icon);
  const slugArg = _attrJs(a.slug || '');
  const nameArg = _attrJs(a.name || '');
  const iconUrlArg = _attrJs(iconSrc);
  const iconClassArg = _attrJs(appIconClass);
  const colorArg = _attrJs(color);
  const iconHtml = iconSrc
    ? `<img src="${iconSrc}" style="width:28px;height:28px;object-fit:contain;" alt="${_esc(a.name)}">`
    : `<i class="${appIconClass}" style="color:${color};font-size:20px;"></i>`;
  const actionBtn = a.is_activated
    ? `<button class="btn btn-secondary btn-sm" onclick="event.stopPropagation();deactivateApp(${slugArg},${nameArg},${iconUrlArg},${iconClassArg},${colorArg})"><i class="fas fa-plug-circle-xmark"></i></button>`
    : `<button class="btn btn-primary btn-sm" onclick="event.stopPropagation();activateApp(${slugArg},${nameArg},${a.is_free},${a.has_trial},${iconUrlArg},${iconClassArg},${colorArg})"><i class="fas fa-plug"></i> ${MKT_I18N.installButton}</button>`;

  return `
  <div class="app-list-item" onclick="openAppModal(${slugArg})">
    <div style="width:44px;height:44px;border-radius:12px;background:${iconBg};display:flex;align-items:center;justify-content:center;flex-shrink:0;">${iconHtml}</div>
    <div style="flex:1;min-width:0;">
      <div style="font-weight:var(--fw-semi);color:var(--c-ink);display:flex;align-items:center;gap:8px;">
        ${_esc(a.name)}
        ${a.is_activated ? `<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;">${MKT_I18N.activatedBadge}</span>` : ''}
      </div>
      <div style="font-size:12.5px;color:var(--c-ink-40);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${_esc(a.tagline || a.description || '')}</div>
    </div>
    <div style="flex-shrink:0;display:flex;align-items:center;gap:12px;">
      <span style="font-size:12px;color:var(--c-ink-60);">${a.is_free ? MKT_I18N.freeLabel : _esc(a.pricing_label)}</span>
      ${actionBtn}
    </div>
  </div>`;
}

/* ── Pagination ─────────────────────────────────────────────────────────── */
function renderPagination(data) {
  const wrap = document.getElementById('paginationWrapper');
  if (!wrap || data.last_page <= 1) { wrap.innerHTML = ''; return; }
  const { current_page, last_page } = data;
  const pages = [];
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i);
  wrap.innerHTML = `
    <button class="page-btn" ${current_page<=1?'disabled':''} onclick="goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
    ${pages.map(p => `<button class="page-btn ${p===current_page?'active':''}" onclick="goTo(${p})">${p}</button>`).join('')}
    <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
}
function goTo(p) { state.page = p; loadApps(); window.scrollTo({top:0,behavior:'smooth'}); }

/* ── Actions ─────────────────────────────────────────────────────────────── */
function buildAppConfirmIcon(name, iconUrl, iconClass) {
  const safeName = _esc(name || '');
  const safeUrl = String(_iconSource(iconUrl) || '').replace(/"/g, '&quot;');

  if (safeUrl) {
    return `<img src="${safeUrl}" alt="${safeName}">`;
  }

  return `<i class="${_iconClass(iconClass || 'fas fa-puzzle-piece')}"></i>`;
}

async function activateApp(slug, name, isFree, hasTrial, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  const msg = hasTrial
    ? MKT_I18N.activateTrialTitle.replace(':name', name)
    : (isFree ? MKT_I18N.activateFreeTitle.replace(':name', name) : MKT_I18N.activatePaidTitle.replace(':name', name));

  Modal.confirm({
    title: msg,
    message: hasTrial ? MKT_I18N.activateMessageTrial : MKT_I18N.activateMessageDefault,
    confirmText: hasTrial ? MKT_I18N.activateConfirmTrial : MKT_I18N.activateConfirmInstall,
    type: 'success',
    iconHtml: buildAppConfirmIcon(name, iconUrl, iconClass),
    iconVariant: 'app',
    iconColor: color,
    onConfirm: async () => {
      const { ok, data } = await Http.post(marketplaceRoute(window.MKT_ROUTES.activate, slug), {});
      if (ok) {
        Toast.success(MKT_I18N.activateSuccess, data.message);
        if (data.redirect) {
          setTimeout(() => { window.location.href = data.redirect; }, 450);
          return;
        }
        loadApps();
      } else {
        Toast.error(MKT_I18N.error, data.message);
      }
    }
  });
}

async function deactivateApp(slug, name, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  Modal.confirm({
    title: MKT_I18N.deactivateTitle.replace(':name', name),
    message: MKT_I18N.deactivateMessage,
    confirmText: MKT_I18N.deactivateConfirm,
    type: 'danger',
    iconHtml: buildAppConfirmIcon(name, iconUrl, iconClass),
    iconVariant: 'app',
    iconColor: color,
    onConfirm: async () => {
      const { ok, data } = await Http.post(marketplaceRoute(window.MKT_ROUTES.deactivate, slug), {});
      if (ok) { Toast.success(MKT_I18N.deactivateSuccess, data.message); loadApps(); }
      else Toast.error(MKT_I18N.error, data.message);
    }
  });
}

async function openAppModal(slug) {
  // Charger la page show en AJAX pour afficher dans la modal
  window.location.href = marketplaceRoute(window.MKT_ROUTES.show, slug);
}

function _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
function _attrJs(value) { return JSON.stringify(String(value ?? '')).replace(/"/g, '&quot;'); }
function marketplaceRoute(template, slug) {
  return String(template).replace('__SLUG__', encodeURIComponent(String(slug)));
}
function _iconClass(value, fallback = 'fas fa-puzzle-piece') {
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

function _iconSource(value) {
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

// Init
document.addEventListener('DOMContentLoaded', () => loadApps());
</script>
@endpush

