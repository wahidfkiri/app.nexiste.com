<?php

namespace App\Http\Requests;

use App\Support\Security\InputSanitizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class SecureFormRequest extends FormRequest
{
    /**
     * Champs à exclure de la sanitization.
     *
     * @var string[]
     */
    protected array $exceptSanitize = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'token',
        '_token',
    ];

    /**
     * Champs autorisés à garder du HTML.
     *
     * @var string[]
     */
    protected array $allowHtmlFields = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sanitized = InputSanitizer::sanitizeArray(
            $this->all(),
            [
                'strip_tags' => true,
                'except' => array_merge((array) config('security.sanitize.except', []), $this->exceptSanitize),
                'allow_html' => array_merge((array) config('security.sanitize.allow_html', []), $this->allowHtmlFields),
            ]
        );

        $this->merge($sanitized);
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->ajax() || $this->isJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation. Veuillez corriger les champs.',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
