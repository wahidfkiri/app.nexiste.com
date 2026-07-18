<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.clients') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.index') ? 'active' : '' }}">{{ __('common.nav.list') }}</a>
    <a href="{{ route('clients.create') }}" class="{{ request()->routeIs('clients.create') ? 'active' : '' }}">{{ __('common.nav.new_client') }}</a>
    <a href="{{ route('clients.export.excel') }}">{{ __('common.nav.export_excel') }}</a>
  </div>
</div>
