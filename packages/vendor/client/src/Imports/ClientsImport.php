<?php

namespace Vendor\Client\Imports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Vendor\Client\Models\Client;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Client([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'company_name' => $row['company_name'],
            'contact_name' => $row['contact_name'] ?? null,
            'email' => $row['email'],
            'phone' => $row['phone'] ?? null,
            'type' => $row['type'] ?? 'entreprise',
            'status' => $row['status'] ?? 'actif',
            'source' => $row['source'] ?? 'direct',
            'city' => $row['city'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
            'country' => $row['country'] ?? null,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) (Auth::user()->tenant_id ?? 0);

        return [
            'company_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('clients', 'email')->where(function ($query) use ($tenantId) {
                    return $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],
            'type' => 'in:entreprise,particulier,startup',
            'status' => 'in:actif,inactif,en_attente',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'company_name.required' => __('client::clients.validation.company_name.required'),
            'email.required' => __('client::clients.validation.email.required'),
            'email.email' => __('client::clients.validation.email.email'),
            'email.unique' => __('client::clients.validation.email.unique'),
            'type.in' => __('client::clients.validation.type.in'),
            'status.in' => __('client::clients.validation.status.in'),
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'company_name' => __('client::clients.fields.company_name'),
            'contact_name' => __('client::clients.fields.contact_name'),
            'email' => __('client::clients.fields.email'),
            'phone' => __('client::clients.fields.phone'),
            'type' => __('client::clients.fields.type'),
            'status' => __('client::clients.fields.status'),
            'source' => __('client::clients.fields.source'),
            'city' => __('client::clients.fields.city'),
            'postal_code' => __('client::clients.fields.postal_code'),
            'country' => __('client::clients.fields.country'),
        ];
    }
}
