<?php

namespace Vendor\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(config('invoice.payment_methods', []))),
            'reference' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => __('invoice::invoices.fields.amount'),
            'currency' => __('invoice::invoices.fields.currency'),
            'payment_date' => __('invoice::invoices.fields.payment_date'),
            'payment_method' => __('invoice::invoices.fields.payment_method'),
            'reference' => __('invoice::invoices.fields.reference'),
            'bank_name' => __('invoice::invoices.fields.bank_name'),
            'notes' => __('invoice::invoices.fields.notes'),
            'attachment' => __('invoice::invoices.fields.attachment'),
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => __('invoice::invoices.validation.amount_required'),
            'amount.min' => __('invoice::invoices.validation.amount_min'),
            'payment_date.required' => __('invoice::invoices.validation.payment_date_required'),
            'payment_method.required' => __('invoice::invoices.validation.payment_method_required'),
        ];
    }
}
