<?php

namespace EslovCustomisation\Shim;

/**
 * Preloads patched Municipio Term helper before theme autoload (icon/colour cache collision).
 */
class MunicipioTermCacheFix
{
    public static function register(): void
    {
        if (class_exists(\Municipio\Helper\Term\Term::class, false)) {
            return;
        }

        require_once ESLOV_CUSTOMISATION_PATH . 'source/php/Shim/Municipio/Helper/Term/Term.php';
    }
}
