<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

/**
 * Pins primary button colors after Municipio V4.1 strips them.
 *
 * When legacy button_primary_color_active is off, DecorateDesignTokens unsets
 * --c-button--color--primary*. Filled primary buttons then fall back to styleguide
 * color-mix defaults (not in DB / not editable). LTS used the palette primary.
 */
class ButtonPrimaryColorCorrection implements DesignTokenCorrectionInterface
{
    private const PRIMARY_PATH = [
        'component',
        '__general__',
        'button',
        '--c-button--color--primary',
    ];

    private const CONTRAST_PATH = [
        'component',
        '__general__',
        'button',
        '--c-button--color--primary-contrast',
    ];

    public function apply(DesignTokenState $state): void
    {
        $primary = $state->getValue(['token', '--color--primary'])
            ?? LegacyThemeModReader::palettePrimaryBase();
        if ($primary === null) {
            return;
        }

        $state->applyChange(
            self::PRIMARY_PATH,
            $primary,
            sprintf(
                'Set --c-button--color--primary to %s (palette primary; replaces V4.1-stripped color-mix fallback)',
                $primary,
            ),
        );

        $contrast = $state->getValue(['token', '--color--primary-contrast'])
            ?? LegacyThemeModReader::palettePrimaryContrasting();
        if ($contrast === null) {
            return;
        }

        $state->applyChange(
            self::CONTRAST_PATH,
            $contrast,
            sprintf(
                'Set --c-button--color--primary-contrast to %s (palette primary contrast)',
                $contrast,
            ),
        );
    }
}
