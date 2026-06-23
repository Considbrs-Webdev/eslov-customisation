<?php

namespace EslovCustomisation\Migration\DesignTokenCorrections;

use EslovCustomisation\Migration\DesignTokenCorrectionInterface;
use EslovCustomisation\Migration\DesignTokenState;

class SearchFormShapeCorrection implements DesignTokenCorrectionInterface
{
    public function apply(DesignTokenState $state): void
    {
        if (get_theme_mod('search_form_shape') !== '100') {
            return;
        }

        $state->applyChange(
            ['token', '--c-search-form-border-radius'],
            '100px',
            'Set search form border-radius to 100px (search_form_shape pill)',
        );
    }
}
