<?php

declare(strict_types=1);

namespace EslovCustomisation\Support;

use EslovCustomisation\AcfFields\BrandPaletteFields;

/**
 * Resolves a page-tree brand palette and merges its colours into Municipio token JSON.
 *
 * @phpstan-type Palette array{
 *     name: string,
 *     slug: string,
 *     root_page: int,
 *     colors: array<string, string>
 * }
 */
class BrandPalette
{
    /**
     * ACF colour field → root design token.
     *
     * @var array<string, string>
     */
    private const COLOR_TOKEN_MAP = [
        'color_primary' => '--color--primary',
        'color_primary_contrast' => '--color--primary-contrast',
        'color_secondary' => '--color--secondary',
        'color_secondary_contrast' => '--color--secondary-contrast',
    ];

    /**
     * Component tokens that currently pin a hex copy of palette colours.
     *
     * @var array<string, list<list<string>>>
     */
    private const COMPONENT_MIRRORS = [
        'color_primary' => [
            ['__general__', 'button', '--c-button--color--primary'],
        ],
        'color_primary_contrast' => [
            ['__general__', 'button', '--c-button--color--primary-contrast'],
            ['__general__', 'header', '--c-header--color--primary-contrast'],
        ],
    ];

    /**
     * @var Palette|null|false
     */
    private static $requestPalette = false;

    /**
     * Palette for the current frontend page request, if any.
     *
     * @return Palette|null
     */
    public static function current(): ?array
    {
        if (self::$requestPalette !== false) {
            return self::$requestPalette;
        }

        if (is_admin() || is_customize_preview()) {
            self::$requestPalette = null;

            return null;
        }

        if (!did_action('wp')) {
            return null;
        }

        if (!is_singular('page')) {
            self::$requestPalette = null;

            return null;
        }

        $postId = (int) get_queried_object_id();
        if ($postId <= 0) {
            self::$requestPalette = null;

            return null;
        }

        self::$requestPalette = self::forPost($postId);

        return self::$requestPalette;
    }

    /**
     * @return Palette|null
     */
    public static function forPost(int $postId): ?array
    {
        $palettes = self::all();
        if ($palettes === []) {
            return null;
        }

        $chain = array_merge([$postId], array_map('intval', get_post_ancestors($postId)));

        return self::match($palettes, $chain);
    }

    /**
     * First palette whose root page appears in the chain (current page, then parents).
     *
     * @param list<Palette> $palettes
     * @param list<int>     $ancestorChain
     *
     * @return Palette|null
     */
    public static function match(array $palettes, array $ancestorChain): ?array
    {
        $byRoot = [];

        foreach ($palettes as $palette) {
            $root = (int) ($palette['root_page'] ?? 0);
            if ($root > 0 && !isset($byRoot[$root])) {
                $byRoot[$root] = $palette;
            }
        }

        foreach ($ancestorChain as $id) {
            $id = (int) $id;
            if (isset($byRoot[$id])) {
                return $byRoot[$id];
            }
        }

        return null;
    }

    /**
     * @return list<Palette>
     */
    public static function all(): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $rows = get_field(BrandPaletteFields::REPEATER_NAME, 'option');
        if (!is_array($rows)) {
            return [];
        }

        $palettes = [];

        foreach ($rows as $row) {
            $palette = self::normalizeRow($row);
            if ($palette !== null) {
                $palettes[] = $palette;
            }
        }

        return $palettes;
    }

    /**
     * Merge palette colours into a Municipio `theme_mod('tokens')` JSON string.
     */
    public static function applyToTokenJson(string $json, array $palette): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $decoded = ['token' => [], 'component' => []];
        }

        if (!isset($decoded['token']) || !is_array($decoded['token'])) {
            $decoded['token'] = [];
        }

        if (!isset($decoded['component']) || !is_array($decoded['component'])) {
            $decoded['component'] = [];
        }

        $colors = $palette['colors'] ?? [];
        if (!is_array($colors)) {
            return $json;
        }

        foreach (self::COLOR_TOKEN_MAP as $field => $token) {
            if (!isset($colors[$field])) {
                continue;
            }

            $decoded['token'][$token] = $colors[$field];

            foreach (self::COMPONENT_MIRRORS[$field] ?? [] as $path) {
                self::setNestedValue($decoded['component'], $path, $colors[$field]);
            }
        }

        $encoded = wp_json_encode($decoded);

        return is_string($encoded) ? $encoded : $json;
    }

    /**
     * @param mixed $row
     *
     * @return Palette|null
     */
    private static function normalizeRow($row): ?array
    {
        if (!is_array($row)) {
            return null;
        }

        $name = trim((string) ($row['name'] ?? ''));
        $rootPage = (int) ($row['root_page'] ?? 0);

        if ($name === '' || $rootPage <= 0) {
            return null;
        }

        $colors = [];

        foreach (array_keys(self::COLOR_TOKEN_MAP) as $field) {
            $hex = self::sanitizeHex($row[$field] ?? null);
            if ($hex !== null) {
                $colors[$field] = $hex;
            }
        }

        if (!isset($colors['color_primary'], $colors['color_primary_contrast'])) {
            return null;
        }

        return [
            'name' => $name,
            'slug' => sanitize_title($name),
            'root_page' => $rootPage,
            'colors' => $colors,
        ];
    }

    /**
     * @param mixed $value
     */
    private static function sanitizeHex($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $hex = sanitize_hex_color($value);

        return is_string($hex) && $hex !== '' ? $hex : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $path
     */
    private static function setNestedValue(array &$data, array $path, string $value): void
    {
        $ref = &$data;
        $last = array_pop($path);

        if (!is_string($last) || $last === '') {
            return;
        }

        foreach ($path as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }

            $ref = &$ref[$segment];
        }

        $ref[$last] = $value;
    }
}
