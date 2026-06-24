<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class FieldBorderRadiusCorrection implements DesignTokenCorrectionInterface
{
    /** @var array<string, string> scope key => human label */
    private const LEGACY_SCOPED_PATHS = [
        'scope:s-header' => 'header scope',
        'scope:s-post-type-page' => 'page scope',
    ];

    public function apply(DesignTokenState $state): void
    {
        $this->removeLegacyScopedFieldRadius($state);

        $radius = LegacyThemeModReader::fieldBorderRadius();
        if ($radius === null) {
            return;
        }

        $state->applyChange(
            ['component', '__general__', 'field', '--c-field--border-radius'],
            $radius,
            sprintf(
                'Set field --c-field--border-radius to %s (__general__; legacy field_border_radius)',
                $radius,
            ),
        );
    }

    private function removeLegacyScopedFieldRadius(DesignTokenState $state): void
    {
        foreach (self::LEGACY_SCOPED_PATHS as $scope => $label) {
            $state->removeValue(
                ['component', $scope, 'field', '--c-field--border-radius'],
                sprintf('Remove scoped field --c-field--border-radius from %s', $label),
            );
        }
    }
}
