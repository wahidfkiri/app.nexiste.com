<?php

namespace Vendor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
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
