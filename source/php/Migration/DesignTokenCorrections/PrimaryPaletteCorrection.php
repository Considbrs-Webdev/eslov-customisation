<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class PrimaryPaletteCorrection implements DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void
    {
        $contrast = LegacyThemeModReader::navPrimaryContrastingColor();
        if ($contrast === null) {
            return;
        }

        $state->applyChange(
            ['token', '--color--primary-contrast'],
            $contrast,
            sprintf('Set --color--primary-contrast to %s (nav_h_color_primary.contrasting)', $contrast),
        );

        $state->applyChange(
            ['component', '__general__', 'header', '--c-header--color--primary-contrast'],
            $contrast,
            sprintf(
                'Set header primary contrast to %s (nav_h_color_primary.contrasting)',
                $contrast,
            ),
        );
    }
}
