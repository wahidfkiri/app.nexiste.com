<?php

namespace Vendor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:stock_suppliers,id',
            'reference' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,ordered,received,cancelled',
            'order_date' => 'nullable|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'nullable|exists:stock_articles,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.unit_price' => 'required|numeric|min:0',
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
