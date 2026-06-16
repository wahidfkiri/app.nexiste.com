<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('stock::stock.pages.delivery_notes.pdf.title', ['number' => $deliveryNote->number]) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DM Sans", sans-serif; font-size: 10pt; color: #0f172a; margin: 0; }
        .wrap { padding: 34px 36px 40px; }
        .header-band { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: bold; color: #0f766e; }
        .subtle { color: #64748b; font-size: 9pt; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .grid td { width: 50%; border: 1px solid #e2e8f0; padding: 10px 12px; vertical-align: top; }
        .grid .label { font-size: 8pt; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .items { width: 100%; border-collapse: collapse; }
        .items th { background: #0f766e; color: #fff; padding: 8px; text-align: left; font-size: 8pt; text-transform: uppercase; }
        .items td { border: 1px solid #e2e8f0; padding: 8px; font-size: 9pt; }
        .right { text-align: right; }
        .note { margin-top: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #0f766e; padding: 12px; border-radius: 6px; }
        .footer { margin-top: 20px; font-size: 8pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header-band">
        <div class="title">{{ $deliveryNote->number }}</div>
        <div class="subtle">{{ $deliveryNote->type_label }} - {{ __('stock::stock.pages.delivery_notes.pdf.status_prefix') }} {{ $deliveryNote->status_label }}</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ __('stock::stock.common.partner') }}</div>
                <strong>{{ $deliveryNote->type === 'in' ? ($deliveryNote->supplier?->name ?: __('stock::stock.common.none_short')) : ($deliveryNote->client?->company_name ?: __('stock::stock.common.none_short')) }}</strong>
            </td>
            <td>
                <div class="label">{{ __('stock::stock.common.date_of_delivery_note') }}</div>
                <strong>{{ optional($deliveryNote->issue_date)->format('d/m/Y') ?: __('stock::stock.common.none_short') }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('stock::stock.common.reference') }}</div>
                <strong>{{ $deliveryNote->reference ?: __('stock::stock.common.none_short') }}</strong>
            </td>
            <td>
                <div class="label">{{ __('stock::stock.common.linked_order') }}</div>
                <strong>{{ $deliveryNote->order?->number ?: __('stock::stock.common.none_short') }}</strong>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('stock::stock.common.article') }}</th>
                <th>{{ __('stock::stock.common.sku') }}</th>
                <th class="right">{{ __('stock::stock.common.quantity') }}</th>
                <th>{{ __('stock::stock.common.unit') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryNote->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku ?: ($item->article?->sku ?: __('stock::stock.common.none_short')) }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 4, ',', ' ') }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($deliveryNote->notes))
        <div class="note">
            <strong>{{ __('stock::stock.common.notes') }}</strong><br>
            {{ $deliveryNote->notes }}
        </div>
    @endif

    <div class="footer">{{ __('stock::stock.common.generated_at', ['date' => now()->format('d/m/Y H:i')]) }}</div>
</div>
</body>
</html>
