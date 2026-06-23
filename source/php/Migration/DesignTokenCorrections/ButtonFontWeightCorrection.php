<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class ButtonFontWeightCorrection implements DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void
    {
        $weight = LegacyThemeModReader::typographyVariant('typography_button');
        if ($weight === null) {
            return;
        }

        $state->applyChange(
            ['component', '__general__', 'button', '--c-button--font-weight-medium'],
            $weight,
            sprintf('Set button font-weight to %s (typography_button)', $weight),
        );
    }
}
