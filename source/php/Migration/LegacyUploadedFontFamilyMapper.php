<?php

namespace EslovCustomisation\Migration;

/**
 * Maps LTS uploaded-font CSS families (attachment titles) onto Municipio
 * Design Builder / native font-library values.
 */
class LegacyUploadedFontFamilyMapper
{
    public const GOOGLE_FAMILY = 'Montserrat';

    public const CSS_FONT_FAMILY = '"Montserrat", sans-serif';

    public static function toCssFontFamily(string $value): ?string
    {
        $family = self::firstFamilyName($value);

        if ($family === '') {
            return null;
        }

        if (self::isMontserratFamily($family)) {
            return self::CSS_FONT_FAMILY;
        }

        return null;
    }

    public static function toKirkiFontFamily(string $value): ?string
    {
        $family = self::firstFamilyName($value);

        if ($family === '') {
            return null;
        }

        if (self::isMontserratFamily($family) && $family !== self::GOOGLE_FAMILY) {
            return self::GOOGLE_FAMILY;
        }

        return null;
    }

    public static function mapsToMontserrat(string $value): bool
    {
        return self::isMontserratFamily(self::firstFamilyName($value));
    }

    public static function firstFamilyName(string $value): string
    {
        $first = trim(strtok($value, ',') ?: $value);

        return trim($first, " \t\n\r\0\x0B\"'");
    }

    private static function isMontserratFamily(string $family): bool
    {
        $normalized = strtolower($family);

        if ($normalized === 'montserrat') {
            return true;
        }

        return str_starts_with($normalized, 'montserrat-variablefont')
            || str_starts_with($normalized, 'montserrat-italic-variablefont');
    }
}
