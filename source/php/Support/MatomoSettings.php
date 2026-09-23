<?php

declare(strict_types=1);

namespace EslovCustomisation\Support;

/**
 * Single reader for the Matomo options, with a one-way fallback to values left in
 * wp_options by the old municipio-extended "Tracking" page.
 */
class MatomoSettings
{
    public const KEYS = ['url', 'container_id', 'site_id'];

    public static function get(string $key): string
    {
        $value = function_exists('get_field') ? get_field("eslov_matomo_{$key}", 'option') : '';

        if (empty($value)) {
            $value = self::legacy($key);
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * ACF stores options-page values as `options_{name}`; the plain name covers hand-set options.
     */
    public static function legacy(string $key): string
    {
        foreach (["options_mx_matomo_{$key}", "mx_matomo_{$key}"] as $option) {
            $value = get_option($option);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    public static function requireConsent(): bool
    {
        return function_exists('get_field') && (bool) get_field('eslov_matomo_require_consent', 'option');
    }
}
