<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

class LegacyThemeModReader
{
    public static function typographyVariant(string $themeModKey): ?string
    {
        $value = get_theme_mod($themeModKey);
        if (!is_array($value)) {
            return null;
        }

        $variant = $value['variant'] ?? null;
        if (!is_string($variant) || $variant === '' || $variant === 'regular') {
            return null;
        }

        return $variant;
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
}
