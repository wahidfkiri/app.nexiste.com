<?php

namespace Vendor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:in,out',
            'supplier_id' => 'nullable|exists:stock_suppliers,id|required_if:type,in',
            'client_id' => 'nullable|exists:clients,id|required_if:type,out',
            'stock_order_id' => 'nullable|exists:stock_orders,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'reference' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'notes' => 'nullable|string|max:4000',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'nullable|exists:stock_articles,id',
            'items.*.stock_order_item_id' => 'nullable|exists:stock_order_items,id',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:2000',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return trans('stock::stock.validation');
    }

    public function attributes(): array
    {
        return trans('stock::stock.attributes');
    }
}
