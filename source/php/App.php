<?php

namespace EslovCustomisation;

class App
{
    /**
     * @var object[]
     */
    private array $instances = [];

    public function __construct()
    {
        $this->registerInstances();
    }

    /**
     * Register customisation class instances. Each class wires hooks in __construct().
     */
    private function registerInstances(): void
    {
        $classes = [
            Customisations\Config::class,
            Customisations\ModularityColumnWidth::class,
            Customisations\Templates::class,
            Customisations\TopSidebarLayout::class,
            AcfFields\ModNavigationFields::class,
            Customisations\ChildPageLinksBelowTitle::class,
            Customisations\TaxonomyTaglist::class,
        ];

        foreach ($classes as $class) {
            if (class_exists($class)) {
                $this->instances[] = new $class();
            }
        }
    }

    /**
     * @param class-string $className
     */
    public function getInstance(string $className): ?object
    {
        foreach ($this->instances as $instance) {
            if ($instance instanceof $className) {
                return $instance;
            }
        }

        return null;
    }
}
