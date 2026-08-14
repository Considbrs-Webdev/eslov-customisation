<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Support\BrandPalette;

/**
 * Applies a section palette by merging colours into Municipio token JSON for the request.
 */
class BrandPaletteOverride
{
    public function __construct()
    {
        add_filter('theme_mod_tokens', [$this, 'filterTokens'], 20);
        add_filter('body_class', [$this, 'addBodyClass']);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function filterTokens($value)
    {
        $palette = BrandPalette::current();
        if ($palette === null || !is_string($value) || $value === '') {
            return $value;
        }

        return BrandPalette::applyToTokenJson($value, $palette);
    }

    /**
     * @param string[] $classes
     *
     * @return string[]
     */
    public function addBodyClass(array $classes): array
    {
        $palette = BrandPalette::current();
        if ($palette === null) {
            return $classes;
        }

        $classes[] = 'eslov-brand-palette';

        $slug = $palette['slug'] ?? '';
        if (is_string($slug) && $slug !== '') {
            $classes[] = 'eslov-brand-palette--' . $slug;
        }

        return $classes;
    }
}
