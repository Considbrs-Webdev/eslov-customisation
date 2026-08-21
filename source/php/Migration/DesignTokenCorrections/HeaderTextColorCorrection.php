<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

/**
 * Fixes --c-header--color on surface headers when V4.1 mapped nav_h_color_primary.contrasting globally.
 */
class HeaderTextColorCorrection implements DesignTokenCorrectionInterface
{
    private const TOKEN_PATH = [
        'component',
        '__general__',
        'header',
        '--c-header--color',
    ];

    public function apply(DesignTokenState $state): void
    {
        if (!$this->isSurfaceHeader()) {
            return;
        }

        $intended = $this->resolveIntendedColor($state);
        if ($intended === null) {
            return;
        }

        $current = $state->getValue(self::TOKEN_PATH);
        $headerColor = LegacyThemeModReader::headerColor();

        if (LegacyThemeModReader::colorsMatch($current, $intended)) {
            return;
        }

        if ($current === null && ($headerColor === null || !str_starts_with($headerColor, 'text-'))) {
            return;
        }

        if (!$this->canWrite($state, $current, $headerColor)) {
            return;
        }

        $message = sprintf(
            'Set --c-header--color to %s (surface header; legacy header_color=%s; replaces V4.1 nav_h_color_primary.contrasting on header)',
            $intended,
            $headerColor ?? 'default',
        );

        $navLeftover = LegacyThemeModReader::navPrimaryContrastingColor();
        if (
            $current !== null
            && $navLeftover !== null
            && LegacyThemeModReader::colorsMatch($current, $navLeftover)
        ) {
            $state->removeValue(
                self::TOKEN_PATH,
                sprintf(
                    'Remove erroneous --c-header--color from nav_h_color_primary.contrasting (%s)',
                    $current,
                ),
            );
        }

        $state->applyChange(self::TOKEN_PATH, $intended, $message);
    }

    private function canWrite(DesignTokenState $state, ?string $current, ?string $headerColor): bool
    {
        if ($state->isForce()) {
            return true;
        }

        if ($current === null) {
            return $headerColor !== null && str_starts_with($headerColor, 'text-');
        }

        $navLeftover = LegacyThemeModReader::navPrimaryContrastingColor();

        return $navLeftover !== null && LegacyThemeModReader::colorsMatch($current, $navLeftover);
    }

    private function isSurfaceHeader(): bool
    {
        $background = LegacyThemeModReader::headerBackground();

        if ($background === null || $background === '') {
            return true;
        }

        return !in_array($background, ['primary', 'secondary'], true);
    }

    private function resolveIntendedColor(DesignTokenState $state): ?string
    {
        $headerColor = LegacyThemeModReader::headerColor();

        return match ($headerColor) {
            'text-black' => '#000000',
            'text-white' => '#ffffff',
            'text-primary' => LegacyThemeModReader::palettePrimaryBase()
                ?? $state->getValue(['token', '--color--primary']),
            'text-secondary' => LegacyThemeModReader::paletteSecondaryBase()
                ?? $state->getValue(['token', '--color--secondary']),
            default => $state->getValue(['token', '--color--surface-contrast']) ?? '#000000',
        };
    }
}
