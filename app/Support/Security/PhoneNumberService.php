<?php

namespace App\Support\Security;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberService
{
    private ?PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = class_exists(PhoneNumberUtil::class) ? PhoneNumberUtil::getInstance() : null;
    }

    public function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public function hasLeadingZero(string $value): bool
    {
        $digits = $this->digits($value);
        return $digits !== '' && str_starts_with($digits, '0');
    }

    public function isValidForCountry(string $value, string $countryCode): bool
    {
        $value = trim($value);
        $countryCode = strtoupper(trim($countryCode));

        if ($value === '' || $countryCode === '') {
            return false;
        }

        // Fallback if libphonenumber is unavailable.
        if (!$this->phoneUtil) {
            return (bool) preg_match('/^[0-9\s().-]{6,30}$/', $value);
        }

        try {
            $phoneNumber = $this->phoneUtil->parse($value, $countryCode);
        } catch (NumberParseException) {
            return false;
        }

        return $this->phoneUtil->isValidNumberForRegion($phoneNumber, $countryCode);
    }

    public function normalizeToE164ForCountry(string $value, string $countryCode): ?string
    {
        $value = trim($value);
        $countryCode = strtoupper(trim($countryCode));

        if ($value === '' || $countryCode === '' || !$this->phoneUtil) {
            return null;
        }

        try {
            $phoneNumber = $this->phoneUtil->parse($value, $countryCode);
        } catch (NumberParseException) {
            return null;
        }

        if (!$this->phoneUtil->isValidNumberForRegion($phoneNumber, $countryCode)) {
            return null;
        }

        return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
    }

    public function isValidInternational(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || !$this->phoneUtil) {
            return false;
        }

        if (!str_starts_with($value, '+')) {
            return false;
        }

        try {
            $phoneNumber = $this->phoneUtil->parse($value, 'ZZ');
        } catch (NumberParseException) {
            return false;
        }

        return $this->phoneUtil->isValidNumber($phoneNumber);
    }

    public function normalizeInternational(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || !$this->phoneUtil || !str_starts_with($value, '+')) {
            return null;
        }

        try {
            $phoneNumber = $this->phoneUtil->parse($value, 'ZZ');
        } catch (NumberParseException) {
            return null;
        }

        if (!$this->phoneUtil->isValidNumber($phoneNumber)) {
            return null;
        }

        return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
    }
}
