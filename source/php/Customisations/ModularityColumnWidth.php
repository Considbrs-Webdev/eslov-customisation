<?php

namespace EslovCustomisation\Customisations;

/**
 * Restore Municipio grid column classes on module wrappers after DB import.
 *
 * Imported modularity-modules meta often has columnWidth: "" (empty). Display.php then
 * outputs an empty third class instead of falling back to o-grid-12. Legacy widget data
 * uses grid-md-12 which must become o-grid-12@md.
 */
class ModularityColumnWidth
{
    public function __construct()
    {
        add_filter('Modularity/Display/BeforeModule::classes', [$this, 'normalizeModuleClasses'], 10, 4);
        add_filter('Modularity/Widget/ColumnWidth', [$this, 'normalizeColumnWidth'], 10, 2);
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function normalizeModuleClasses(array $classes, array $args, string $postType, int $moduleId): array
    {
        foreach ($classes as $index => $class) {
            if (!$this->isColumnWidthClass($class)) {
                continue;
            }

            $classes[$index] = $this->normalizeColumnWidth($class, []);
        }

        return $classes;
    }

    public function normalizeColumnWidth(string $columnWidth, array $instance): string
    {
        if ($columnWidth === '') {
            return 'o-grid-12@md';
        }

        $columnWidth = (string) apply_filters('Modularity/Display/replaceGrid', $columnWidth);

        if ($columnWidth === 'o-grid-12') {
            return 'o-grid-12@md';
        }

        return $columnWidth;
    }

    private function isColumnWidthClass(string $class): bool
    {
        if ($class === '') {
            return true;
        }

        return (bool) preg_match('/^(?:grid-md-\d+|o-grid-\d+(?:@\w+)?)$/', $class);
    }
}
