@extends('layouts.global')

@section('title', __('stock::stock.pages.delivery_notes.show.title'))

@section('breadcrumb')
  <a href="{{ route('stock.delivery-notes.index') }}">{{ __('stock::stock.common.delivery_notes') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $deliveryNote->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $deliveryNote->number }}</h1><p>{{ $deliveryNote->type_label }} - {{ $deliveryNote->status_label }}</p></div>
  <div class="page-header-actions">
    <a href="{{ route('stock.delivery-notes.pdf', $deliveryNote) }}" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> {{ __('stock::stock.common.pdf') }}</a>
    @if($deliveryNote->status === 'draft')
      <button class="btn btn-secondary" onclick="validateDeliveryNote()"><i class="fas fa-circle-check"></i> {{ __('stock::stock.common.validate') }}</button>
      <a href="{{ route('stock.delivery-notes.edit', $deliveryNote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> {{ __('stock::stock.common.edit') }}</a>
    @elseif($deliveryNote->status === 'validated')
      <button class="btn btn-danger" onclick="cancelDeliveryNote()"><i class="fas fa-ban"></i> {{ __('stock::stock.common.cancel') }}</button>
    @endif
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-4" style="padding-right:12px;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-info-circle"></i> {{ __('stock::stock.pages.delivery_notes.show.section_information') }}</h3>
      <div class="row">
        <div class="col-12"><strong>{{ __('stock::stock.common.type') }}</strong><div>{{ $deliveryNote->type_label }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.status') }}</strong><div>{{ $deliveryNote->status_label }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.date') }}</strong><div>{{ optional($deliveryNote->issue_date)->format('Y-m-d') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.reference') }}</strong><div>{{ $deliveryNote->reference ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.supplier') }}</strong><div>{{ $deliveryNote->supplier?->name ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.client') }}</strong><div>{{ $deliveryNote->client?->company_name ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.linked_order') }}</strong><div>@if($deliveryNote->order)<a href="{{ route('stock.orders.show', $deliveryNote->order) }}">{{ $deliveryNote->order->number }}</a>@else - @endif</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.validated_at') }}</strong><div>{{ $deliveryNote->validated_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.cancelled_at') }}</strong><div>{{ $deliveryNote->cancelled_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>{{ __('stock::stock.common.notes') }}</strong><div>{{ $deliveryNote->notes ?: '-' }}</div></div>
      </div>
    </div>
  </div>
  <div class="col-8" style="padding-left:12px;">
    <div class="table-wrapper" style="margin-bottom:18px;">
      <div class="table-header"><span class="table-title">{{ __('stock::stock.pages.delivery_notes.show.section_lines') }}</span></div>
      <table class="crm-table">
        <thead><tr><th>{{ __('stock::stock.common.article') }}</th><th>{{ __('stock::stock.common.sku') }}</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.unit') }}</th></tr></thead>
        <tbody>
          @foreach($deliveryNote->items as $item)
            <tr>
              <td>{{ $item->name }}</td>
              <td>{{ $item->sku ?: ($item->article?->sku ?: '-') }}</td>
              <td>{{ $item->quantity }}</td>
              <td>{{ $item->unit }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="table-wrapper">
      <div class="table-header"><span class="table-title">{{ __('stock::stock.pages.delivery_notes.show.section_movements') }}</span></div>
      <table class="crm-table">
        <thead><tr><th>{{ __('stock::stock.common.date') }}</th><th>{{ __('stock::stock.common.article') }}</th><th>{{ __('stock::stock.common.directions') }}</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.reason') }}</th></tr></thead>
        <tbody>
          @forelse($deliveryNote->movements as $movement)
            <tr>
              <td>{{ optional($movement->happened_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>{{ $movement->article?->name ?: '-' }}</td>
              <td>{{ $movement->direction_label }}</td>
              <td>{{ $movement->quantity }}</td>
              <td>{{ $movement->display_reason ?: '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-arrows-rotate"></i></div><h3>{{ __('stock::stock.pages.delivery_notes.show.no_movements_title') }}</h3><p>{{ __('stock::stock.pages.delivery_notes.show.no_movements_description') }}</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
async function validateDeliveryNote() {
  Modal.confirm({
    title: @json(__('stock::stock.pages.delivery_notes.show.validate_title')),
    message: @json(__('stock::stock.pages.delivery_notes.show.validate_message')),
    confirmText: @json(__('stock::stock.common.validate')),
    type: 'warning',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route('stock.delivery-notes.validate', $deliveryNote) }}', {});
      if (!ok || !data.success) {
        Toast.error(@json(__('stock::stock.common.error')), data.message || @json(__('stock::stock.pages.delivery_notes.show.validate_error')));
        return;
      }

      Toast.success(@json(__('stock::stock.common.success')), data.message || @json(__('stock::stock.pages.delivery_notes.show.validate_success')));

      if (data.automation?.should_prompt && window.AutomationSuggestions) {
        const flow = window.AutomationSuggestions.open(data.automation, {
          redirectUrl: data.redirect || null,
        });

        await Promise.resolve(flow).finally(() => {
          window.location.href = data.redirect || window.location.href;
        });

        return;
      }

      window.location.href = data.redirect || window.location.href;
    }
  });
}

async function cancelDeliveryNote() {
  Modal.confirm({
    title: @json(__('stock::stock.pages.delivery_notes.show.cancel_title')),
    message: @json(__('stock::stock.pages.delivery_notes.show.cancel_message')),
    confirmText: @json(__('stock::stock.pages.delivery_notes.show.cancel_confirm')),
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route('stock.delivery-notes.cancel', $deliveryNote) }}', {});
      if (!ok || !data.success) {
        Toast.error(@json(__('stock::stock.common.error')), data.message || @json(__('stock::stock.pages.delivery_notes.show.cancel_error')));
        return;
      }
      Toast.success(@json(__('stock::stock.common.success')), data.message || @json(__('stock::stock.pages.delivery_notes.show.cancel_success')));
      window.location.href = data.redirect || window.location.href;
    }
  });
}
</script>
@endpush
