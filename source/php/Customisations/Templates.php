<?php

namespace EslovCustomisation\Customisations;

class Templates
{
    public function __construct()
    {
        add_filter('Municipio/viewPaths', [$this, 'registerViewPaths'], 10, 1);
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    public function registerViewPaths(array $paths): array
    {
        return array_merge($paths, [ESLOV_CUSTOMISATION_PATH . 'views']);
    }
}
