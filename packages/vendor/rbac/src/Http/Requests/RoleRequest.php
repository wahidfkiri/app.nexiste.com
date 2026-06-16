<?php

namespace Vendor\Rbac\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => __('rbac::rbac.validation.label_required'),
            'label.max' => __('rbac::rbac.validation.label_max'),
            'color.regex' => __('rbac::rbac.validation.color_regex'),
            'permissions.*.exists' => __('rbac::rbac.validation.permission_exists'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('rbac::rbac.messages.validation_errors'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
