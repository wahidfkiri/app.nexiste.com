<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.stock') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('stock.articles.index') }}" class="{{ request()->routeIs('stock.articles.*') ? 'active' : '' }}">{{ __('common.nav.articles') }}</a>
    <a href="{{ route('stock.suppliers.index') }}" class="{{ request()->routeIs('stock.suppliers.*') ? 'active' : '' }}">{{ __('common.nav.suppliers') }}</a>
    <a href="{{ route('stock.orders.index') }}" class="{{ request()->routeIs('stock.orders.*') ? 'active' : '' }}">{{ __('common.nav.orders') }}</a>
  </div>
</div>
