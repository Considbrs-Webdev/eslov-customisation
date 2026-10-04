<?php

namespace EslovCustomisation\Customisations;

/** Local table experiment; keep upstream components untouched. */
class TableLayout
{
    public function __construct()
    {
        add_filter('ComponentLibrary/ViewPaths', [$this, 'registerComponentViewPath']);
    }

    public function registerComponentViewPath(array $viewPaths): array
    {
        $path = ESLOV_CUSTOMISATION_PATH . 'views/components';
        if (!in_array($path, $viewPaths, true)) {
            array_unshift($viewPaths, $path);
        }

        return $viewPaths;
    }
}
