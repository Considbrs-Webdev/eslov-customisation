<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class DrawerLinkContrastCorrection implements DesignTokenCorrectionInterface
{
    private const TOKEN_PATH = [
        'component',
        'scope:s-drawer',
        'header',
        '--c-nav--color--surface-contrast',
    ];

    public function apply(DesignTokenState $state): void
    {
        $legacyContrast = LegacyThemeModReader::navDrawerContrastingColor();
        if ($legacyContrast === null) {
            return;
        }

        $current = $state->getValue(self::TOKEN_PATH);
        if ($current === null) {
            return;
        }

        if (!$this->colorsMatch($current, $legacyContrast) && !$state->isForce()) {
            return;
        }

        $state->removeValue(
            self::TOKEN_PATH,
            sprintf(
                'Remove drawer --c-nav--color--surface-contrast (%s from nav_v_color_drawer.contrasting; duotone secondary inherits --color--primary-contrast)',
                $current,
            ),
        );
    }

    private function colorsMatch(string $a, string $b): bool
    {
        return strtolower(trim($a)) === strtolower(trim($b));
    }
}
