<?php

namespace App\Services\Sms;

class PhoneNumberNormalizer
{
    private string $countryCode;

    private bool $restrictToDefaultCountry;

    public function __construct(?string $countryCode = null, ?bool $restrictToDefaultCountry = null)
    {
        $this->countryCode = ltrim(
            (string) ($countryCode ?? config('sms.default_country_code', '233')),
            '+',
        );
        $this->restrictToDefaultCountry = $restrictToDefaultCountry
            ?? (bool) config('sms.restrict_to_default_country', true);
    }

    /**
     * Normalize local and international numbers to E.164.
     *
     * Ghanaian mobile examples:
     * 024 123 4567, 233241234567, +233241234567 and 00233241234567
     * all normalize to +233241234567.
     */
    public function normalize(?string $phone): ?string
    {
        $input = trim((string) $phone);
        if ($input === '' || ! preg_match('/\d/', $input)) {
            return null;
        }

        $isExplicitInternational = str_starts_with($input, '+') || str_starts_with($input, '00');
        $digits = (string) preg_replace('/\D+/', '', $input);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! $isExplicitInternational) {
            if (str_starts_with($digits, $this->countryCode)) {
                // Already contains the configured country code without a plus.
            } elseif (str_starts_with($digits, '0')) {
                $digits = $this->countryCode.substr($digits, 1);
            } else {
                $digits = $this->countryCode.$digits;
            }
        }

        $normalized = '+'.$digits;
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $normalized)) {
            return null;
        }

        if ($this->restrictToDefaultCountry && ! str_starts_with($digits, $this->countryCode)) {
            return null;
        }

        // Ghanaian mobile subscriber numbers contain nine digits and begin
        // with 2 or 5 after the country code.
        if ($this->countryCode === '233' && ! preg_match('/^\+233[25]\d{8}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
