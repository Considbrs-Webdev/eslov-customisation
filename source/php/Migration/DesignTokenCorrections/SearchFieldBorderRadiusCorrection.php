<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class SearchFieldBorderRadiusCorrection implements DesignTokenCorrectionInterface
{
    /** Design Builder slider value for rounded search field left edge. */
    private const BORDER_RADIUS = '4';

    /** @var array<string, string> scope key => human label */
    private const SCOPES = [
        'scope:s-header' => 'header search',
        'scope:s-post-type-page' => 'hero search on pages',
    ];

    public function apply(DesignTokenState $state): void
    {
        foreach (self::SCOPES as $scope => $label) {
            $state->applyChange(
                ['component', $scope, 'field', '--c-field--border-radius'],
                self::BORDER_RADIUS,
                sprintf(
                    'Set field --c-field--border-radius to %s for %s (search field left corners)',
                    self::BORDER_RADIUS,
                    $label,
                ),
            );
        }
    }
}
