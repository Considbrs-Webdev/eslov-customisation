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
            Customisations\SiteStyles::class,
            Customisations\SiteScripts::class,
            Customisations\BrandedBorder::class,
            Customisations\ModularityColumnWidth::class,
            Customisations\Templates::class,
            Customisations\TopSidebarLayout::class,
            Customisations\NestedModularityShortcodes::class,
            Customisations\ManualInputLinkNormalize::class,
            Customisations\CustomerFeedback::class,
            Admin\CustomerFeedbackSettings::class,
            AcfFields\BrandPaletteFields::class,
            Customisations\BrandPaletteOverride::class,
            AcfFields\ModNavigationFields::class,
            AcfFields\ModPostsFilteringFields::class,
            AcfFields\PageSectionStartField::class,
            AcfFields\TermRedirectToField::class,
            Customisations\SingularTaxonomyCustomizer::class,
            Customisations\ModPostsTaxonomyFiltering::class,
            Customisations\ModPostsHideTermIcons::class,
            Customisations\ChildPageLinksBelowTitle::class,
            Customisations\SectionStartPageLink::class,
            Customisations\TaxonomyTaglist::class,
            Customisations\TimelineActiveStep::class,
            Customisations\SectionModuleWysiwyg::class,
            Customisations\SectionModuleGap::class,
            Customisations\ExternalContent\SubsiteImportReview::class,
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
