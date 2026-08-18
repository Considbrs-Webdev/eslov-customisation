<?php

namespace EslovCustomisation\Customisations;

/**
 * Dated timelines: compact one-sided layout (reuse sequential CSS) plus
 * current/passed step state. Sequential mode is left to upstream.
 */
class TimelineActiveStep
{
    public function __construct()
    {
        add_filter('ComponentLibrary/Component/Timeline/Data', [$this, 'applyStepStates']);
        add_filter('ComponentLibrary/ViewPaths', [$this, 'registerComponentViewPath']);
    }

    /**
     * @param array<int, string> $viewPaths
     * @return array<int, string>
     */
    public function registerComponentViewPath(array $viewPaths): array
    {
        array_unshift($viewPaths, ESLOV_CUSTOMISATION_PATH . 'views/components');

        return $viewPaths;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function applyStepStates(array $data): array
    {
        $data = $this->applyDatedCompactLayout($data);

        if (!empty($data['sequential'])) {
            return $data;
        }

        if (!isset($data['events']) || !is_array($data['events']) || $data['events'] === []) {
            return $data;
        }

        $isTimeMode = $this->isTimeMode($data['events']);
        $now = $isTimeMode ? date('H:i:s') : date('Y-m-d');
        $pattern = $isTimeMode ? '/^\d{2}:\d{2}:\d{2}$/' : '/^\d{4}-\d{2}-\d{2}$/';
        $activeIndex = null;
        $closestValue = null;

        foreach ($data['events'] as $index => $event) {
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

        foreach ($data['events'] as $index => $event) {
            if (!is_array($event)) {
                continue;
            }

            $value = $isTimeMode ? ($event['timestamp'] ?? null) : ($event['date'] ?? null);

            if (!is_string($value) || !preg_match($pattern, $value)) {
                continue;
            }

            if ($index === $activeIndex) {
                $data['events'][$index]['active_step'] = true;
                continue;
            }

            if ($value < $now) {
                $data['events'][$index]['passed_step'] = true;
            }
        }

        return $data;
    }

    /**
     * Reuse styleguide sequential layout CSS without turning sequential mode on
     * (dates still belong on the card).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function applyDatedCompactLayout(array $data): array
    {
        if (!empty($data['sequential'])) {
            return $data;
        }

        if (!isset($data['classList']) || !is_array($data['classList'])) {
            $data['classList'] = [];
        }

        $data['classList'][] = 'c-timeline--sequential';

        return $data;
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
