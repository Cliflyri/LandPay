<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PhoneNumberService
{
    public function e164(?string $phone, string $country = 'US'): ?string
    {
        $value = trim((string) $phone);
        if ($value === '') return null;
        if (str_starts_with($value, '+')) {
            $digits = preg_replace('/\D+/', '', $value);
            return $digits && strlen($digits) >= 8 && strlen($digits) <= 15 ? '+'.$digits : null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (in_array(strtoupper($country), ['US', 'CA'], true)) {
            if (strlen($digits) === 10) return '+1'.$digits;
            if (strlen($digits) === 11 && str_starts_with($digits, '1')) return '+'.$digits;
        }
        return null;
    }

    public function requiredE164(?string $phone, string $country = 'US'): string
    {
        return $this->e164($phone, $country)
            ?? throw ValidationException::withMessages(['phone' => 'Enter a valid phone number including country code.']);
    }
}
