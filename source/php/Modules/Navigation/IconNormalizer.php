<?php

namespace EslovCustomisation\Modules\Navigation;

class IconNormalizer
{
    /**
     * @param mixed $icon ACF icon field (string, array with name, etc.)
     * @return array{icon: string, size?: string, color?: string, customColor?: string}|null
     */
    public static function forComponent(mixed $icon, string $default = 'arrow_forward'): ?array
    {
        $name = self::resolveName($icon, $default);
        if ($name === '') {
            return null;
        }

        return [
            'icon' => $name,
            'size' => 'md',
        ];
    }

    /**
     * Icon/text contrast via Municipio design tokens (not a fixed hex).
     *
     * @param array<string, mixed> $icon
     * @param string $background Token slug matching --color--{slug} (e.g. primary, secondary).
     * @return array<string, mixed>
     */
    public static function withContrastOn(array $icon, string $background = 'primary'): array
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($background)) ?: 'primary';
        unset($icon['color']);
        $icon['customColor'] = 'var(--color--' . $slug . '-contrast)';

        return $icon;
    }

    /**
     * Material icon name for @button (expects string|false, not an icon array).
     */
    public static function forButton(mixed $icon, string $default = ''): string|false
    {
        $name = self::resolveName($icon, $default);

        return $name !== '' ? $name : false;
    }

    public static function resolveName(mixed $icon, string $default = 'arrow_forward'): string
    {
        if (is_array($icon)) {
            $name = $icon['name'] ?? $icon['icon'] ?? $icon['value'] ?? '';
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        if (is_string($icon) && $icon !== '') {
            return $icon;
        }

        return $default;
    }
}
