<?php

namespace EslovCustomisation\Migration;

/**
 * Converts legacy Modularity WP_Widget placements to block widgets with [modularity] shortcodes.
 *
 * Classic widgets (widget_modularity-module) break in the block widget editor and are not
 * scanned by Modularity::hasModule(), so module CSS may not load. Block shortcode widgets
 * are editable and detected via widget_block content.
 */
class ClassicWidgetMigrator
{
    private const CLASSIC_WIDGET_PREFIX = 'modularity-module-';

    private const CLASSIC_OPTION = 'widget_modularity-module';

    private const BLOCK_OPTION = 'widget_block';

    public function __construct(
        private readonly bool $dryRun = false,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        $sidebars = get_option('sidebars_widgets', []);
        if (!is_array($sidebars)) {
            $result->addMessage('No sidebars_widgets option found.');
            return $result;
        }

        $classicWidgets = get_option(self::CLASSIC_OPTION, []);
        if (!is_array($classicWidgets)) {
            $classicWidgets = [];
        }

        $blockWidgets = get_option(self::BLOCK_OPTION, []);
        if (!is_array($blockWidgets)) {
            $blockWidgets = [];
        }

        foreach ($sidebars as $sidebarId => $widgetIds) {
            if ($sidebarId === 'array_version' || $sidebarId === 'wp_inactive_widgets' || !is_array($widgetIds)) {
                continue;
            }

            foreach ($widgetIds as $index => $widgetId) {
                if (!is_string($widgetId) || !str_starts_with($widgetId, self::CLASSIC_WIDGET_PREFIX)) {
                    continue;
                }

                $instanceId = (int) substr($widgetId, strlen(self::CLASSIC_WIDGET_PREFIX));
                $config = $classicWidgets[$instanceId] ?? null;

                if (!is_array($config) || empty($config['module_id'])) {
                    $result->errors++;
                    $result->addMessage(sprintf(
                        'Missing config for %s in sidebar %s',
                        $widgetId,
                        $sidebarId,
                    ));
                    continue;
                }

                $moduleId = (int) $config['module_id'];
                $moduleType = (string) ($config['module_type'] ?? get_post_type($moduleId) ?: 'unknown');
                $title = (string) ($config['title'] ?? get_the_title($moduleId));

                if ($this->sidebarAlreadyHasModule($sidebars[$sidebarId], $blockWidgets, $moduleId)) {
                    $result->skipped++;
                    $result->addMessage(sprintf(
                        'Skip %s (%s, module %d): sidebar %s already has block shortcode for this module',
                        $widgetId,
                        $title,
                        $moduleId,
                        $sidebarId,
                    ));
                    continue;
                }

                $blockInstanceId = $this->nextBlockInstanceId($blockWidgets);
                $blockWidgetId = 'block-' . $blockInstanceId;
                $content = $this->buildShortcodeBlockContent($moduleId);
                $blockWidgets[$blockInstanceId] = ['content' => $content];

                $result->addMessage(sprintf(
                    '%s %s → %s in %s (module %d %s, %s)',
                    $this->dryRun ? 'Would migrate' : 'Migrated',
                    $widgetId,
                    $blockWidgetId,
                    $sidebarId,
                    $moduleId,
                    $title,
                    $moduleType,
                ));

                if ($this->dryRun) {
                    $result->migrated++;
                    continue;
                }

                $sidebars[$sidebarId][$index] = $blockWidgetId;
                unset($classicWidgets[$instanceId]);
                $result->migrated++;
            }
        }

        $this->purgeInactiveClassicWidgets($sidebars, $classicWidgets, $result);

        if (!$this->dryRun && $result->migrated > 0) {
            update_option(self::BLOCK_OPTION, $blockWidgets);
            update_option('sidebars_widgets', $sidebars);
            update_option(self::CLASSIC_OPTION, $classicWidgets);
        }

        return $result;
    }

    /**
     * Remove inactive legacy widgets. The block widget editor still tries to load them
     * and shows modularity-module errors even though they are not used on the frontend.
     *
     * @param array<string, mixed> $sidebars
     * @param array<int|string, mixed> $classicWidgets
     */
    private function purgeInactiveClassicWidgets(
        array &$sidebars,
        array &$classicWidgets,
        MigrationResult $result,
    ): void {
        $inactive = $sidebars['wp_inactive_widgets'] ?? [];

        if (!is_array($inactive)) {
            return;
        }

        $remaining = [];

        foreach ($inactive as $widgetId) {
            if (!is_string($widgetId) || !str_starts_with($widgetId, self::CLASSIC_WIDGET_PREFIX)) {
                $remaining[] = $widgetId;
                continue;
            }

            $instanceId = (int) substr($widgetId, strlen(self::CLASSIC_WIDGET_PREFIX));
            $title = (string) ($classicWidgets[$instanceId]['title'] ?? $widgetId);

            $result->addMessage(sprintf(
                '%s inactive %s (%s)',
                $this->dryRun ? 'Would remove' : 'Removed',
                $widgetId,
                $title,
            ));

            if (!$this->dryRun) {
                unset($classicWidgets[$instanceId]);
                $result->migrated++;
            } else {
                $result->migrated++;
            }
        }

        if (count($remaining) !== count($inactive)) {
            $sidebars['wp_inactive_widgets'] = $remaining;
        }
    }

    /**
     * @param array<int|string, mixed> $sidebarWidgetIds
     * @param array<int|string, mixed> $blockWidgets
     */
    private function sidebarAlreadyHasModule(array $sidebarWidgetIds, array $blockWidgets, int $moduleId): bool
    {
        foreach ($sidebarWidgetIds as $widgetId) {
            if (!is_string($widgetId) || !str_starts_with($widgetId, 'block-')) {
                continue;
            }

            $instanceId = (int) substr($widgetId, strlen('block-'));
            $content = $blockWidgets[$instanceId]['content'] ?? '';

            if (!is_string($content)) {
                continue;
            }

            if (preg_match('/\[modularity[^\]]*id=["\']?' . $moduleId . '["\']?/', $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $blockWidgets
     */
    private function nextBlockInstanceId(array $blockWidgets): int
    {
        $max = 0;

        foreach (array_keys($blockWidgets) as $key) {
            if (is_numeric($key)) {
                $max = max($max, (int) $key);
            }
        }

        return $max + 1;
    }

    private function buildShortcodeBlockContent(int $moduleId): string
    {
        $shortcode = sprintf('[modularity id="%d"]', $moduleId);

        return "<!-- wp:shortcode -->\n{$shortcode}\n<!-- /wp:shortcode -->";
    }
}
