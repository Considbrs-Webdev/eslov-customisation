<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class BoldFontWeightCorrection implements DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void
    {
        $weight = LegacyThemeModReader::typographyVariant('typography_bold');
        if ($weight === null) {
            return;
        }

        $state->applyChange(
            ['token', '--font-weight-bold'],
            $weight,
            sprintf('Set --font-weight-bold to %s (typography_bold)', $weight),
        );
    }
}
