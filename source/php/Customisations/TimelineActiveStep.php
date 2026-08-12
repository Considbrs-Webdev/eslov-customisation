<?php

namespace EslovCustomisation\Customisations;

/**
 * Auto-highlight the timeline event closest to (but not after) today or current time.
 */
class TimelineActiveStep
{
    public function __construct()
    {
        add_filter('Modularity/Block/acf/timeline/Data', [$this, 'applyToBlockData'], 10, 3);
        add_filter('Modularity/Display/mod-timeline/viewData', [$this, 'applyToModuleViewData']);
    }

    /**
     * @param array<string, mixed> $viewData
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function applyToBlockData(array $viewData, array $block, object $module): array
    {
        return $this->applyActiveStep($viewData);
    }

    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    public function applyToModuleViewData(array $viewData): array
    {
        return $this->applyActiveStep($viewData);
    }

    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    private function applyActiveStep(array $viewData): array
    {
        if (!empty($viewData['sequential'])) {
            return $viewData;
        }

        if (!isset($viewData['events']) || !is_array($viewData['events']) || $viewData['events'] === []) {
            return $viewData;
        }

        $isTimeMode = $this->isTimeMode($viewData['events']);
        $now = $isTimeMode ? date('H:i:s') : date('Y-m-d');
        $pattern = $isTimeMode ? '/^\d{2}:\d{2}:\d{2}$/' : '/^\d{4}-\d{2}-\d{2}$/';
        $activeIndex = null;
        $closestValue = null;

        foreach ($viewData['events'] as $index => $event) {
            if (!is_array($event)) {
                continue;
            }

            $value = $isTimeMode ? ($event['timestamp'] ?? null) : ($event['date'] ?? null);

            if (!is_string($value) || !preg_match($pattern, $value)) {
                continue;
            }

            if ($value > $now) {
                continue;
            }

            if ($closestValue === null || $value > $closestValue) {
                $closestValue = $value;
                $activeIndex = $index;
            }
        }

        if ($activeIndex === null) {
            return $viewData;
        }

        $viewData['events'][$activeIndex]['active_step'] = true;

        return $viewData;
    }

    /**
     * @param array<int, mixed> $events
     */
    private function isTimeMode(array $events): bool
    {
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            return !empty($event['format']);
        }

        return false;
    }
}
