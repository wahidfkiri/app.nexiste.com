<div class="module-toolbar">
  <div class="module-toolbar-title">Stock</div>
  <div class="module-toolbar-links">
    <a href="{{ route('stock.articles.index') }}" class="{{ request()->routeIs('stock.articles.*') ? 'active' : '' }}">Articles</a>
    <a href="{{ route('stock.suppliers.index') }}" class="{{ request()->routeIs('stock.suppliers.*') ? 'active' : '' }}">Fournisseurs</a>
    <a href="{{ route('stock.orders.index') }}" class="{{ request()->routeIs('stock.orders.*') ? 'active' : '' }}">Commandes</a>
  </div>
</div>

