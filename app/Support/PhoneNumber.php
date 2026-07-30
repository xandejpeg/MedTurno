<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

final class PhoneNumber
{
    /**
     * @return list<array{iso: string, name: string, calling_code: string}>
     */
    public static function countries(): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $countries = [];

        foreach ($phoneUtil->getSupportedRegions() as $iso) {
            try {
                $name = Countries::getName($iso, 'pt_BR');
            } catch (MissingResourceException) {
                $name = $iso;
            }

            $countries[] = [
                'iso' => $iso,
                'name' => $name,
                'calling_code' => (string) $phoneUtil->getCountryCodeForRegion($iso),
            ];
        }

        usort($countries, fn (array $left, array $right) => strnatcasecmp($left['name'], $right['name']));

        return $countries;
    }

    public static function toE164(string $country, string $nationalNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $nationalNumber);

        if ($digits === null || $digits === '') {
            return null;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $phone = $phoneUtil->parse($digits, strtoupper($country));

            if (! $phoneUtil->isPossibleNumber($phone)) {
                return null;
            }

            return $phoneUtil->format($phone, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }

    public static function normalizeStored(?string $phone, string $defaultCountry = 'BR'): ?string
    {
        $phone = trim($phone ?? '');

        if ($phone === '') {
            return null;
        }

        if (! str_starts_with($phone, '+')) {
            return self::toE164($defaultCountry, $phone);
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsed = $phoneUtil->parse($phone, 'ZZ');

            if (! $phoneUtil->isPossibleNumber($parsed)) {
                return null;
            }

            return $phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}