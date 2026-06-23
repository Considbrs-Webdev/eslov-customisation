<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class FooterLinkContrastCorrection implements DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void
    {
        $contrast = LegacyThemeModReader::footerTextColor();
        if ($contrast === null) {
            return;
        }

        $state->applyChange(
            ['component', '__general__', 'footer', '--c-link--color--background-contrast'],
            $contrast,
            sprintf(
                'Set footer --c-link--color--background-contrast to %s (footer_color_text; overrides :root link token for plain <a>)',
                $contrast,
            ),
        );

        $state->applyChange(
            ['component', '__general__', 'footer', '--inherit-color-contrast'],
            $contrast,
            sprintf(
                'Set footer --inherit-color-contrast to %s (footer_color_text)',
                $contrast,
            ),
        );
    }
}
