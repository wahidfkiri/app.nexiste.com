<?php

namespace Vendor\Client\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Vendor\Client\Models\Client;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('revenue') && blank($this->input('revenue'))) {
            $this->merge(['revenue' => 0]);
        }
    }

    public function rules(): array
    {
        $routeClient = $this->route('client');
        $clientId = $routeClient instanceof Client ? $routeClient->id : $routeClient;
        $tenantId = (int) ($this->user()?->tenant_id ?? 0);

        $emailRule = Rule::unique('clients', 'email')
            ->where(function ($query) use ($tenantId) {
                return $query
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at');
            });

        if ($clientId) {
            $emailRule->ignore($clientId);
        }

        return [
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
            'siret' => 'nullable|string|max:50',
            'type' => 'required|in:entreprise,particulier,startup,association,public',
            'status' => 'required|in:actif,inactif,en_attente,suspendu',
            'source' => 'nullable|in:direct,site_web,reference,reseau_social,autre',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'revenue' => 'nullable|numeric|min:0',
            'potential_value' => 'nullable|numeric|min:0',
            'payment_term' => 'nullable|in:immediate,15j,30j,45j,60j',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:5000',
            'next_follow_up_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => __('client::clients.validation.company_name.required'),
            'contact_name.required' => __('client::clients.validation.contact_name.required'),
            'email.required' => __('client::clients.validation.email.required'),
            'email.email' => __('client::clients.validation.email.email'),
            'email.unique' => __('client::clients.validation.email.unique'),
            'type.required' => __('client::clients.validation.type.required'),
            'type.in' => __('client::clients.validation.type.in'),
            'status.required' => __('client::clients.validation.status.required'),
            'status.in' => __('client::clients.validation.status.in'),
            'revenue.min' => __('client::clients.validation.revenue.min'),
            'website.url' => __('client::clients.validation.website.url'),
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => __('client::clients.fields.company_name'),
            'contact_name' => __('client::clients.fields.contact_name'),
            'email' => __('client::clients.fields.email'),
            'phone' => __('client::clients.fields.phone'),
            'mobile' => __('client::clients.fields.mobile'),
            'website' => __('client::clients.fields.website'),
            'address' => __('client::clients.fields.address'),
            'city' => __('client::clients.fields.city'),
            'postal_code' => __('client::clients.fields.postal_code'),
            'country' => __('client::clients.fields.country'),
            'vat_number' => __('client::clients.fields.vat_number'),
            'siret' => __('client::clients.fields.siret'),
            'type' => __('client::clients.fields.type'),
            'status' => __('client::clients.fields.status'),
            'source' => __('client::clients.fields.source'),
            'revenue' => __('client::clients.fields.revenue'),
            'notes' => __('client::clients.fields.notes'),
            'next_follow_up_at' => __('client::clients.fields.next_follow_up_at'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('client::clients.messages.validation_failed'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
