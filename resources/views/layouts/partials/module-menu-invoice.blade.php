<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.invoicing') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') || (request()->routeIs('invoices.*') && !request()->routeIs('invoices.quotes.*') && !request()->routeIs('invoices.payments.*') && !request()->routeIs('invoices.reports.*') && !request()->routeIs('invoices.settings.*')) ? 'active' : '' }}">
      <i class="fas fa-file-invoice"></i> {{ __('common.nav.invoices') }}
    </a>
    <a href="{{ route('invoices.quotes.index') }}" class="{{ request()->routeIs('invoices.quotes.*') ? 'active' : '' }}">
      <i class="fas fa-file-signature"></i> {{ __('common.nav.quotes') }}
    </a>
    <a href="{{ route('invoices.payments.index') }}" class="{{ request()->routeIs('invoices.payments.*') ? 'active' : '' }}">
      <i class="fas fa-credit-card"></i> {{ __('common.nav.payments') }}
    </a>
    <a href="{{ route('invoices.reports.index') }}" class="{{ request()->routeIs('invoices.reports.*') ? 'active' : '' }}">
      <i class="fas fa-chart-line"></i> {{ __('common.nav.reports') }}
    </a>
    <a href="{{ route('invoices.settings.index') }}" class="{{ request()->routeIs('invoices.settings.*') ? 'active' : '' }}">
      <i class="fas fa-gear"></i> {{ __('common.nav.settings') }}
    </a>
  </div>
</div>
