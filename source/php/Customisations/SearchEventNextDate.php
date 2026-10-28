<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

/**
 * Show an event's next upcoming date in the Typesense search hit instead of the post date.
 *
 * The hit template renders `post_date_formatted`. For posts whose schemaData is an Event,
 * that field is replaced with the first occasion in `eventSchedule` that has not passed
 * (an occasion counts as passed once its endDate, or startDate if there is none, is behind us),
 * written with the site's date format like other hits: `30 oktober 2026 11:11 - 11:22`. If the occasion ends on another
 * day the end is written in full too. If every occasion has passed, the default date is kept.
 *
 * The value is computed at index time, so it only moves on to the next occasion when the
 * event is reindexed.
 */
class SearchEventNextDate
{
    public function __construct()
    {
        add_filter('Municipio/TypesenseSearch/DocumentBuilder/build', [$this, 'setNextDate'], 20, 2);
    }

    /**
     * @param array<string, mixed> $document
     */
    public function setNextDate(array $document, \WP_Post $post): array
    {
        $schemaData = get_post_meta($post->ID, 'schemaData', true);

        if (!is_array($schemaData) || ($schemaData['@type'] ?? null) !== 'Event') {
            return $document;
        }

        $next = $this->nextOccasion((array) ($schemaData['eventSchedule'] ?? []), time());

        if ($next !== null) {
            $document['post_date_formatted'] = $this->format($next['start'], $next['end']);
        }

        return $document;
    }

    private function format(int $start, ?int $end): string
    {
        $dateFormat = (string) get_option('date_format');
        $text       = wp_date($dateFormat . ' H:i', $start);

        if ($end === null || $end === $start) {
            return $text;
        }

        $sameDay = wp_date('Y-m-d', $start) === wp_date('Y-m-d', $end);

        return $text . ' - ' . wp_date($sameDay ? 'H:i' : $dateFormat . ' H:i', $end);
    }

    /**
     * The earliest occasion that has not passed, or null if there is none.
     *
     * @param array<int, mixed> $schedule
     * @return array{start: int, end: ?int}|null
     */
    private function nextOccasion(array $schedule, int $now): ?array
    {
        $next = null;

        foreach ($schedule as $occasion) {
            if (!is_array($occasion) || empty($occasion['startDate'])) {
                continue;
            }

            $start = $this->localTimestamp((string) $occasion['startDate']);
            $end   = !empty($occasion['endDate']) ? $this->localTimestamp((string) $occasion['endDate']) : null;

            if ($start === null || ($end ?? $start) < $now) {
                continue;
            }

            if ($next === null || $start < $next['start']) {
                $next = ['start' => $start, 'end' => $end];
            }
        }

        return $next;
    }

    /**
     * Event times are stored as the wall-clock time the event takes place, with a misleading
     * `+00:00` offset, so the offset is dropped and the time read in the site's timezone.
     */
    private function localTimestamp(string $dateTime): ?int
    {
        $local = \DateTimeImmutable::createFromFormat('Y-m-d?H:i:s', substr($dateTime, 0, 19), wp_timezone());

        return $local ? $local->getTimestamp() : null;
    }
}
