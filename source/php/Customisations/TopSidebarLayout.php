<?php

namespace EslovCustomisation\Customisations;

/**
 * Top-sidebar layout parity with LTS municipio-extended (o-container + secondary band).
 */
class TopSidebarLayout
{
    private const CONTAINER_TEMPLATE = '<div class="o-container"><div class="o-grid"><div class="o-grid-12@md">{{module-markup}}</div></div></div>';

    public function __construct()
    {
        add_filter('Modularity/Module/Container/Modules', [$this, 'addNavigationModule'], 10, 1);
        add_filter('Modularity/Module/Container/Template', [$this, 'modernContainerTemplate'], 10, 1);
    }

    /**
     * @param string[] $modules
     * @return string[]
     */
    public function addNavigationModule(array $modules): array
    {
        $modules[] = 'mod-navigation';

        return $modules;
    }

    public function modernContainerTemplate(string $template): string
    {
        return self::CONTAINER_TEMPLATE;
    }
}
