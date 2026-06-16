<?php

namespace Vendor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:stock_suppliers,id',
            'sku' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'unit' => 'nullable|string|max:30',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
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
