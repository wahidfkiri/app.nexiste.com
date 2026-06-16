<?php

namespace Vendor\User\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Services\TenantRoleService;

class InviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim((string) $this->input('email')));
        $roleName = $this->input('role_in_tenant');
        $roleId = $this->input('role_id');

        $tenantId = (int) optional($this->user())->tenant_id;
        if ($tenantId > 0) {
            app(TenantRoleService::class)->ensureTenantRoles($tenantId);
        }

        if (!$roleId && is_string($roleName) && $roleName !== '') {
            $resolvedRoleId = Role::query()
                ->where('guard_name', 'web')
                ->where('tenant_id', $tenantId)
                ->where('name', $roleName)
                ->value('id');

            if ($resolvedRoleId) {
                $roleId = (int) $resolvedRoleId;
            }
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => $email,
            'role_id' => $roleId,
        ]);
    }

    public function rules(): array
    {
        $allowedRoles = array_keys(array_diff_key(config('user.tenant_roles', []), ['owner' => '']));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)),
            ],
            'role_in_tenant' => ['required_without:role_id', 'string', Rule::in($allowedRoles)],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $roleId = $this->input('role_id');
            $roleName = (string) $this->input('role_in_tenant', '');

            if ($roleId) {
                $role = Role::query()
                    ->where('id', (int) $roleId)
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)
                    ->first();

                if (!$role) {
                    $validator->errors()->add('role_id', __('user::users.validation.role_not_found'));
                    return;
                }

                if (!array_key_exists($role->name, config('user.tenant_roles', [])) || $role->name === 'owner') {
                    $validator->errors()->add('role_id', __('user::users.hints.owner_not_invitable'));
                    return;
                }

                $this->merge([
                    'role_in_tenant' => $role->name,
                    'role_id' => (int) $role->id,
                ]);
                return;
            }

            if ($roleName !== '') {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)
                    ->first();

                if (!$role) {
                    $validator->errors()->add('role_in_tenant', __('user::users.validation.role_not_found'));
                    return;
                }

                if ($role->name === 'owner') {
                    $validator->errors()->add('role_in_tenant', __('user::users.hints.owner_not_invitable'));
                    return;
                }

                $this->merge(['role_id' => (int) $role->id]);
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('user::users.validation.name_required'),
            'email.required' => __('user::users.validation.invite_email_required'),
            'email.email' => __('user::users.validation.invite_email_invalid'),
            'password.required' => __('user::users.validation.password_required'),
            'password.min' => __('user::users.validation.password_min'),
            'password.confirmed' => __('user::users.validation.password_confirmed'),
            'role_in_tenant.required_without' => __('user::users.validation.role_required'),
            'role_in_tenant.in' => __('user::users.validation.role_invalid'),
            'role_id.exists' => __('user::users.validation.role_not_found'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('user::users.validation.validation_errors'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
