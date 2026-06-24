<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

class LegacyThemeModReader
{
    public static function hasTypographyMod(string $themeModKey): bool
    {
        $value = get_theme_mod($themeModKey);

        return is_array($value) && $value !== [];
    }

    public static function typographyField(string $themeModKey, string $field): ?string
    {
        $value = get_theme_mod($themeModKey);
        if (!is_array($value)) {
            return null;
        }

        $raw = $value[$field] ?? null;
        if (!is_string($raw) && !is_int($raw) && !is_float($raw)) {
            return null;
        }

        $string = trim((string) $raw);

        return $string === '' ? null : $string;
    }

    public static function typographyVariant(string $themeModKey): ?string
    {
        $variant = self::typographyField($themeModKey, 'variant');
        if ($variant === null) {
            return null;
        }

        return self::normalizeVariant($variant);
    }

    public static function normalizeVariant(string $variant): ?string
    {
        if ($variant === 'regular') {
            return '400';
        }

        if ($variant === 'italic' || str_contains($variant, 'italic')) {
            return null;
        }

        return is_numeric($variant) ? $variant : null;
    }

    public static function navPrimaryContrastingColor(): ?string
    {
        $nav = get_theme_mod('nav_h_color_primary');
        if (!is_array($nav)) {
            return null;
        }

        $contrasting = $nav['contrasting'] ?? null;

        return is_string($contrasting) && $contrasting !== '' ? $contrasting : null;
    }

    public static function footerTextColor(): ?string
    {
        $color = get_theme_mod('footer_color_text');

        return is_string($color) && $color !== '' ? $color : null;
    }

    /**
     * LTS Kirki field radius (custom appearance only). Unset mod uses Kirki default "0".
     */
    public static function fieldBorderRadius(): ?string
    {
        if (get_theme_mod('field_appearance_type') !== 'custom') {
            return null;
        }

        $radius = get_theme_mod('field_border_radius');

        if ($radius === false || $radius === null || $radius === '') {
            return '0';
        }

        return is_string($radius) || is_int($radius) || is_float($radius)
            ? (string) $radius
            : '0';
    }
}
