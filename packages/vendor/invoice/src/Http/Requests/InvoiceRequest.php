<?php

namespace Vendor\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'stock_order_id' => 'nullable|exists:stock_orders,id',
            'reference' => 'nullable|string|max:100',
            'status' => 'sometimes|in:draft,sent,viewed,partial,paid,overdue,cancelled,refunded',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'payment_method' => 'nullable|string|max:50',
            'discount_type' => 'nullable|in:none,percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
            'terms' => 'nullable|string|max:5000',
            'footer' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:1000',
            'items.*.article_id' => 'nullable|exists:stock_articles,id',
            'items.*.reference' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,percent,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => __('invoice::invoices.fields.client'),
            'quote_id' => __('invoice::invoices.quotes'),
            'stock_order_id' => 'bon de commande',
            'issue_date' => __('invoice::invoices.fields.issue_date'),
            'due_date' => __('invoice::invoices.fields.due_date'),
            'items' => 'lignes',
            'items.*.description' => __('invoice::invoices.fields.description'),
            'items.*.article_id' => 'article',
            'items.*.quantity' => __('invoice::invoices.fields.quantity'),
            'items.*.unit_price' => __('invoice::invoices.fields.unit_price'),
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => __('invoice::invoices.validation.client_required'),
            'client_id.exists' => __('invoice::invoices.validation.client_exists'),
            'quote_id.exists' => __('invoice::invoices.validation.quote_exists'),
            'stock_order_id.exists' => __('invoice::invoices.validation.stock_order_exists'),
            'issue_date.required' => __('invoice::invoices.validation.issue_date_required'),
            'due_date.after_or_equal' => __('invoice::invoices.validation.due_date_after_or_equal'),
            'items.required' => __('invoice::invoices.validation.items_required'),
            'items.min' => __('invoice::invoices.validation.items_required'),
            'items.*.description.required' => __('invoice::invoices.validation.item_description_required'),
            'items.*.article_id.exists' => __('invoice::invoices.validation.article_exists'),
            'items.*.quantity.required' => __('invoice::invoices.validation.item_quantity_required'),
            'items.*.quantity.min' => __('invoice::invoices.validation.item_quantity_min'),
            'items.*.unit_price.required' => __('invoice::invoices.validation.item_unit_price_required'),
        ];
    }
}
