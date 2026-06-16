<?php

namespace Vendor\User\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Services\TenantRoleService;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'role_id' => $roleId,
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;
        $allowedRoles = array_keys(config('user.tenant_roles', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)),
            ],
            'role_in_tenant' => ['required_without:role_id', 'string', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'inactive', 'invited', 'suspended'])],
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
                    $validator->errors()->add('role_id', __('user::users.validation.role_not_assignable'));
                    return;
                }

                $this->merge([
                    'role_in_tenant' => $role->name,
                    'role_id' => (int) $role->id,
                ]);
            } elseif ($roleName !== '') {
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
                    $validator->errors()->add('role_in_tenant', __('user::users.hints.owner_not_changeable'));
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
            'email.required' => __('user::users.validation.email_required'),
            'email.email' => __('user::users.validation.email_invalid'),
            'email.unique' => __('user::users.validation.email_unique'),
            'role_in_tenant.required_without' => __('user::users.validation.role_required'),
            'role_in_tenant.in' => __('user::users.validation.role_invalid'),
            'status.required' => __('user::users.validation.status_required'),
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