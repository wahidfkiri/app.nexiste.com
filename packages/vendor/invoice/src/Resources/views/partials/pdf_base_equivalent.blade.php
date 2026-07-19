@php
  $__base = strtoupper((string) ($doc->tenant->currency ?? config('invoice.default_currency', 'EUR')));
  $__rate = (float) ($doc->exchange_rate ?? 1);
  $__foreign = strtoupper((string) $doc->currency) !== $__base && $__rate > 0 && abs($__rate - 1.0) > 0.0000001;
@endphp
@if($__foreign)
  <tr>
    <td>{{ __('invoice::invoices.common.equivalent_total') }} {{ $__base }}</td>
    <td class="right">{{ \Vendor\Invoice\Support\Money::format((float) $doc->total * $__rate, $__base) }}</td>
  </tr>
@endif
