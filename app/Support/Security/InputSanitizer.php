<?php

namespace App\Support\Security;

use Illuminate\Http\UploadedFile;
use Normalizer;

class InputSanitizer
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array{
     *   strip_tags?: bool,
     *   except?: string[],
     *   allow_html?: string[]
     * }  $options
     * @return array<string, mixed>
     */
    public static function sanitizeArray(array $data, array $options = []): array
    {
        $stripTags = (bool) ($options['strip_tags'] ?? true);
        $except = array_map('strtolower', (array) ($options['except'] ?? []));
        $allowHtml = array_map('strtolower', (array) ($options['allow_html'] ?? []));

        $sanitized = [];
        foreach ($data as $key => $value) {
            $field = strtolower((string) $key);
            $sanitized[$key] = self::sanitizeValue($value, $field, $stripTags, $except, $allowHtml);
        }

        return $sanitized;
    }

    /**
     * @param  mixed  $value
     * @param  string  $field
     * @param  bool  $stripTags
     * @param  string[]  $except
     * @param  string[]  $allowHtml
     * @return mixed
     */
    private static function sanitizeValue(mixed $value, string $field, bool $stripTags, array $except, array $allowHtml): mixed
    {
        if ($value instanceof UploadedFile || is_numeric($value) || is_bool($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $nestedKey => $nestedValue) {
                $nestedField = $field . '.' . strtolower((string) $nestedKey);
                $clean[$nestedKey] = self::sanitizeValue($nestedValue, $nestedField, $stripTags, $except, $allowHtml);
            }

            return $clean;
        }

        if (!is_string($value)) {
            return $value;
        }

        $string = trim($value);
        $string = str_replace("\0", '', $string);
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $string) ?? $string;

        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        if (class_exists(Normalizer::class) && !Normalizer::isNormalized($string)) {
            $normalized = Normalizer::normalize($string, Normalizer::FORM_C);
            if (is_string($normalized)) {
                $string = $normalized;
            }
        }

        if (self::shouldBypass($field, $except)) {
            return $string;
        }

        if ($stripTags && !self::isAllowedHtmlField($field, $allowHtml)) {
            $string = strip_tags($string);
        }

        // Neutralise les vecteurs XSS courants même si HTML autorisé.
        $string = preg_replace('/<\s*\/?\s*(script|iframe|object|embed|style|link|meta)[^>]*>/i', '', $string) ?? $string;
        $string = preg_replace('/on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $string) ?? $string;
        $string = preg_replace('/javascript\s*:/i', '', $string) ?? $string;

        return $string;
    }

    /**
     * @param  string[]  $except
     */
    private static function shouldBypass(string $field, array $except): bool
    {
        foreach ($except as $candidate) {
            if ($candidate === $field || str_ends_with($field, '.' . $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string[]  $allowHtml
     */
    private static function isAllowedHtmlField(string $field, array $allowHtml): bool
    {
        foreach ($allowHtml as $candidate) {
            if ($candidate === $field || str_ends_with($field, '.' . $candidate)) {
                return true;
            }
        }

        return false;
    }
}
