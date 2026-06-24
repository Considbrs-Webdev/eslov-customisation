<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class FontSizeScaleRatioCorrection implements DesignTokenCorrectionInterface
{
    private const TARGET = '1.125';

    /** Municipio V4.1 MapThemeModsToDesignTokens hardcoded default (Minor Third). */
    private const MUNICIPIO_V41_DEFAULT = '1.200';

    public function apply(DesignTokenState $state): void
    {
        $state->applyChangeWhenUnsetOrCurrentIn(
            ['token', '--font-size-scale-ratio'],
            self::TARGET,
            [self::MUNICIPIO_V41_DEFAULT, '1.2'],
            sprintf(
                'Set --font-size-scale-ratio to %s (Major Second / Stor sekund; closest to prod)',
                self::TARGET,
            ),
        );
    }
}
