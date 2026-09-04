<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations\ExternalContent;

use EslovCustomisation\Sites;
use WP_Post;

/**
 * Before/after table of Event schema fields after a subsite import review.
 *
 * Municipio overwrites `schemaData` on sync. The published copy is stored
 * when status becomes pending so reviewers can see special-field changes
 * below post content. Description is omitted — it is already visible as content.
 */
class SubsiteImportReviewChanges
{
    public const PREVIOUS_SCHEMA_META = '_eslov_review_previous_schema';

    private const CONTENT_FILTER_PRIORITY = 20;

    /**
     * @var array<string, true>
     */
    private const EXCLUDED_TOP_LEVEL_KEYS = [
        'description' => true,
        '@id' => true,
        '@type' => true,
        '@context' => true,
        '@meta' => true,
        'id' => true,
        'checksum' => true,
    ];

    /**
     * @var array<string, true>
     */
    private const COMPARE_IGNORE_NESTED_KEYS = [
        '@id' => true,
        '@meta' => true,
        'id' => true,
        'checksum' => true,
    ];

    /**
     * Previous schemaData captured during wp_insert_post_data, written after insert.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $queuedSchema = [];

    private bool $appended = false;

    public function __construct()
    {
        if (!EventSchemaSettings::isReviewEnabled()) {
            return;
        }

        add_action('wp_insert_post', [self::class, 'persist'], 20, 1);
        add_filter('the_content', [$this, 'appendToContent'], self::CONTENT_FILTER_PRIORITY);
    }

    /**
     * Capture published schemaData in memory. Do not write meta here —
     * update_post_meta inside wp_insert_post_data can abort the status change.
     */
    public static function snapshot(int $postId): void
    {
        if ($postId < 1) {
            return;
        }

        $schema = get_post_meta($postId, 'schemaData', true);
        if (!is_array($schema) || $schema === []) {
            return;
        }

        self::$queuedSchema[$postId] = $schema;
    }

    /**
     * Write the queued previous schema after the post row (and meta_input) is saved.
     */
    public static function persist(int $postId): void
    {
        if (!isset(self::$queuedSchema[$postId])) {
            return;
        }

        $schema = self::$queuedSchema[$postId];
        unset(self::$queuedSchema[$postId]);

        update_post_meta($postId, self::PREVIOUS_SCHEMA_META, $schema);
    }

    /**
     * Drop the stored previous schema after accept or deny.
     */
    public static function clear(int $postId): void
    {
        if ($postId < 1) {
            return;
        }

        delete_post_meta($postId, self::PREVIOUS_SCHEMA_META);
    }

    /**
     * Append a before/after table of changed schema fields after post content.
     *
     * @param mixed $content
     */
    public function appendToContent($content): string
    {
        if (!is_string($content)) {
            return '';
        }

        if ($this->appended) {
            return $content;
        }

        if (!$this->shouldRender()) {
            return $content;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post) {
            return $content;
        }

        $rows = self::changedRows($post->ID);
        if ($rows === []) {
            return $content;
        }

        if (!function_exists('render_blade_view')) {
            return $content;
        }

        $this->appended = true;

        try {
            return $content . render_blade_view('partials.schema.event.import-review-changes', [
                'heading' => __('Ändrade fält', 'eslov-customisation'),
                'columnField' => __('Fält', 'eslov-customisation'),
                'columnBefore' => __('Före', 'eslov-customisation'),
                'columnAfter' => __('Efter', 'eslov-customisation'),
                'rows' => $rows,
            ]);
        } catch (\Throwable) {
            $this->appended = false;

            return $content;
        }
    }

    /**
     * @return array<int, array{field: string, before: string, after: string}>
     */
    public static function changedRows(int $postId): array
    {
        $before = get_post_meta($postId, self::PREVIOUS_SCHEMA_META, true);
        $after = get_post_meta($postId, 'schemaData', true);

        if (!is_array($before) || !is_array($after)) {
            return [];
        }

        return self::diffSchema($before, $after);
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<int, array{field: string, before: string, after: string}>
     */
    public static function diffSchema(array $before, array $after): array
    {
        $before = self::stripExcluded($before);
        $after = self::stripExcluded($after);
        $keys = array_unique([...array_keys($after), ...array_keys($before)]);
        $rows = [];

        foreach ($keys as $key) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if (!self::valuesDiffer($beforeValue, $afterValue)) {
                continue;
            }

            $rows[] = [
                'field' => self::labelForKey($key),
                'before' => self::formatValue($beforeValue),
                'after' => self::formatValue($afterValue),
            ];
        }

        return $rows;
    }

    private function shouldRender(): bool
    {
        if (is_admin() || is_feed() || wp_doing_ajax()) {
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        if (!is_singular() || !Sites::isSubsite()) {
            return false;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post) {
            return false;
        }

        if ($post->post_status !== 'pending') {
            return false;
        }

        return EventSchemaSettings::isEventPostType($post->post_type);
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private static function stripExcluded(array $schema): array
    {
        foreach (array_keys($schema) as $key) {
            if (!is_string($key) || !isset(self::EXCLUDED_TOP_LEVEL_KEYS[$key])) {
                continue;
            }

            unset($schema[$key]);
        }

        return $schema;
    }

    private static function valuesDiffer(mixed $before, mixed $after): bool
    {
        return wp_json_encode(self::normalizeValue($before)) !== wp_json_encode(self::normalizeValue($after));
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = self::maybeReduceImage($value);
        }

        if (!is_array($value)) {
            return self::normalizeScalar($value);
        }

        if ($value === []) {
            return null;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && isset(self::COMPARE_IGNORE_NESTED_KEYS[$key])) {
                continue;
            }

            $normalizedValue = self::normalizeValue($item);
            if ($normalizedValue === null) {
                continue;
            }

            $normalized[$key] = $normalizedValue;
        }

        if ($normalized === []) {
            return null;
        }

        if (!array_is_list($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }

    private static function normalizeScalar(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim(html_entity_decode($value));

            return $trimmed === '' ? null : $trimmed;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>|string
     */
    private static function maybeReduceImage(array $value): array|string
    {
        $type = $value['@type'] ?? '';
        $types = is_array($type) ? $type : [$type];

        if (!in_array('ImageObject', $types, true)) {
            return $value;
        }

        $url = $value['url'] ?? $value['contentUrl'] ?? null;

        return is_string($url) && $url !== '' ? $url : $value;
    }

    private static function labelForKey(string $key): string
    {
        $labels = [
            'name' => __('Namn', 'eslov-customisation'),
            'eventStatus' => __('Status', 'eslov-customisation'),
            'eventAttendanceMode' => __('Deltagande', 'eslov-customisation'),
            'url' => __('Länk', 'eslov-customisation'),
            'typicalAgeRange' => __('Ålder', 'eslov-customisation'),
            'startDate' => __('Startdatum', 'eslov-customisation'),
            'endDate' => __('Slutdatum', 'eslov-customisation'),
            'doorTime' => __('Dörrarna öppnas', 'eslov-customisation'),
            'location' => __('Plats', 'eslov-customisation'),
            'organizer' => __('Arrangör', 'eslov-customisation'),
            'offers' => __('Pris / biljetter', 'eslov-customisation'),
            'eventSchedule' => __('Tillfällen', 'eslov-customisation'),
            'image' => __('Bild', 'eslov-customisation'),
            'keywords' => __('Nyckelord', 'eslov-customisation'),
            'physicalAccessibilityFeatures' => __('Tillgänglighet', 'eslov-customisation'),
            'inLanguage' => __('Språk', 'eslov-customisation'),
            'isAccessibleForFree' => __('Gratis', 'eslov-customisation'),
            'maximumAttendeeCapacity' => __('Max antal deltagare', 'eslov-customisation'),
            'performer' => __('Medverkande', 'eslov-customisation'),
            'about' => __('Om', 'eslov-customisation'),
            'sameAs' => __('Samma som', 'eslov-customisation'),
            'previousStartDate' => __('Tidigare startdatum', 'eslov-customisation'),
            'duration' => __('Längd', 'eslov-customisation'),
            'video' => __('Video', 'eslov-customisation'),
            'audience' => __('Målgrupp', 'eslov-customisation'),
        ];

        return $labels[$key] ?? $key;
    }

    private static function formatValue(mixed $value): string
    {
        if (self::isEmptyValue($value)) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('Ja', 'eslov-customisation') : __('Nej', 'eslov-customisation');
        }

        if (is_scalar($value)) {
            return html_entity_decode(trim((string) $value));
        }

        if (!is_array($value)) {
            return '—';
        }

        $value = self::maybeReduceImage($value);

        if (is_string($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            $parts = array_map(self::formatValue(...), $value);
            $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== '—'));

            return $parts === [] ? '—' : implode("\n\n", $parts);
        }

        return self::formatAssociative($value);
    }

    /**
     * @param array<string, mixed> $value
     */
    private static function formatAssociative(array $value): string
    {
        $lines = [];

        foreach ($value as $key => $item) {
            if (!is_string($key) || isset(self::COMPARE_IGNORE_NESTED_KEYS[$key]) || $key === '@type') {
                continue;
            }

            $formatted = self::formatValue($item);
            if ($formatted === '—') {
                continue;
            }

            $lines[] = self::labelForKey($key) . ': ' . $formatted;
        }

        return $lines === [] ? '—' : implode("\n", $lines);
    }

    private static function isEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        return false;
    }
}
