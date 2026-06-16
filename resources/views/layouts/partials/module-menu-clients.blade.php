<div class="module-toolbar">
  <div class="module-toolbar-title">Clients</div>
  <div class="module-toolbar-links">
    <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.index') ? 'active' : '' }}">Liste</a>
    <a href="{{ route('clients.create') }}" class="{{ request()->routeIs('clients.create') ? 'active' : '' }}">Nouveau client</a>
    <a href="{{ route('clients.export.excel') }}">Export Excel</a>
  </div>
</div>

