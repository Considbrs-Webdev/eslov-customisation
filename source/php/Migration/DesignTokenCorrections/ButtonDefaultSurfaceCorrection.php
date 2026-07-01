<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class ButtonDefaultSurfaceCorrection implements DesignTokenCorrectionInterface
{
    private const TOKEN_PATH = [
        'component',
        '__general__',
        'button',
        '--c-button--color--surface-alt',
    ];

    private const TARGET = '#ffffff';

    /** Legacy Kirki color_button_default.base migrated by Municipio V4.1. */
    private const LEGACY_MIGRATED = '#dedede';

    public function apply(DesignTokenState $state): void
    {
        $current = $state->getValue(self::TOKEN_PATH);

        if ($current === self::TARGET) {
            return;
        }

        if ($current !== null && $current !== self::LEGACY_MIGRATED && !$state->isForce()) {
            return;
        }

        if ($current === self::LEGACY_MIGRATED) {
            $state->removeValue(
                self::TOKEN_PATH,
                'Remove legacy migrated default button grey (color_button_default)',
            );
        }

        $state->applyChange(
            self::TOKEN_PATH,
            self::TARGET,
            'Set default filled button surface to white (LTS mxui bg-button / hub parity)',
        );
    }
}
