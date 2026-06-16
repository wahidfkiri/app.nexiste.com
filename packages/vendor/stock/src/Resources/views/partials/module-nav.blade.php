@php
  $stockNavItems = [
      ['route' => 'stock.articles.index', 'label' => __('stock::stock.module_nav.articles'), 'icon' => 'fas fa-boxes-stacked'],
      ['route' => 'stock.suppliers.index', 'label' => __('stock::stock.module_nav.suppliers'), 'icon' => 'fas fa-building'],
      ['route' => 'stock.orders.index', 'label' => __('stock::stock.module_nav.orders'), 'icon' => 'fas fa-truck-loading'],
      ['route' => 'stock.delivery-notes.index', 'label' => __('stock::stock.module_nav.delivery_notes'), 'icon' => 'fas fa-truck-ramp-box'],
      ['route' => 'stock.movements.index', 'label' => __('stock::stock.module_nav.movements'), 'icon' => 'fas fa-arrows-rotate'],
  ];
@endphp

<div class="module-subnav" style="display:flex;gap:10px;flex-wrap:wrap;margin:0 0 18px;">
  @foreach($stockNavItems as $item)
    @php
      $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']));
    @endphp
    <a
      href="{{ route($item['route']) }}"
      class="btn {{ $active ? 'btn-primary' : 'btn-secondary' }}"
      style="{{ $active ? '' : 'background:#fff;' }}"
    >
      <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
    </a>
  @endforeach
</div>
