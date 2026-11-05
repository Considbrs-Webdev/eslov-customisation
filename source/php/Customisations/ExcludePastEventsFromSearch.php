<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

/**
 * Drops passed events from the Typesense index without unpublishing them.
 *
 * The archive already hides these events. Search reads the index, which keeps
 * a published event until the document is removed. shouldIndex stops new
 * indexing. An hourly cron removes documents whose latest startDate has
 * passed. `_eslov_search_hidden_start` stores that date so the same document
 * is not deleted from Typesense again on every run.
 */
class ExcludePastEventsFromSearch
{
    private const HIDDEN = '_eslov_search_hidden_start';

    private const HOOK = 'eslov_deindex_past_events';

    /** @var string[]|null */
    private ?array $eventPostTypes = null;

    public function __construct()
    {
        add_filter('Municipio/TypesenseSearch/Indexer/shouldIndex', [$this, 'shouldIndex'], 10, 2);
        add_action('init', [$this, 'schedule']);
        add_action(self::HOOK, [$this, 'deindexPastEvents']);
    }

    /**
     * Keep a published event out of the index once every occasion has passed.
     *
     * @param bool $shouldIndex Whether the search plugin would otherwise index the post.
     */
    public function shouldIndex(bool $shouldIndex, \WP_Post $post): bool
    {
        if (!$shouldIndex || !$this->isEventPost($post)) {
            return $shouldIndex;
        }

        $latest = $this->latestStart($post->ID);

        if ($latest === null) {
            return true;
        }

        if ($latest < $this->now()) {
            return false;
        }

        if (get_post_meta($post->ID, self::HIDDEN, true) !== '') {
            delete_post_meta($post->ID, self::HIDDEN);
        }

        return true;
    }

    /**
     * Schedule the hourly removal. The existing site cron runner picks it up.
     */
    public function schedule(): void
    {
        if (wp_next_scheduled(self::HOOK)) {
            return;
        }

        wp_schedule_event(time(), 'hourly', self::HOOK);
    }

    /**
     * Remove index documents for events whose latest startDate has passed.
     */
    public function deindexPastEvents(): void
    {
        if (!$this->typesenseIsUsable()) {
            return;
        }

        $now = $this->now();

        foreach ($this->eventsWithAPassedDate($now) as $postId) {
            $latest = $this->latestStart($postId);

            if ($latest === null || $latest >= $now) {
                continue;
            }

            if ((string) get_post_meta($postId, self::HIDDEN, true) === $latest) {
                continue;
            }

            do_action('typesense_search/deindex_post', $postId);
            update_post_meta($postId, self::HIDDEN, $latest);
        }
    }

    /**
     * Latest occasion start, normalised to Y-m-d H:i:s.
     */
    private function latestStart(int $postId): ?string
    {
        $latest = null;

        foreach (get_post_meta($postId, 'startDate', false) as $value) {
            if (!is_scalar($value) || (string) $value === '') {
                continue;
            }

            $time = strtotime((string) $value);

            if ($time === false) {
                continue;
            }

            $formatted = date('Y-m-d H:i:s', $time);

            if ($latest === null || $formatted > $latest) {
                $latest = $formatted;
            }
        }

        return $latest;
    }

    /**
     * Clock used by the event archive when it hides passed events.
     */
    private function now(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    /**
     * Published events that have at least one startDate before $now.
     *
     * A later occasion can still keep the event in the index. deindexPastEvents()
     * checks the latest date before removing anything.
     *
     * @return int[]
     */
    private function eventsWithAPassedDate(string $now): array
    {
        $postTypes = $this->eventPostTypes();

        if ($postTypes === []) {
            return [];
        }

        $ids = get_posts([
            'post_type' => $postTypes,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'meta_query' => [
                [
                    'key' => 'startDate',
                    'value' => $now,
                    'compare' => '<',
                    'type' => 'DATETIME',
                ],
            ],
        ]);

        return array_map('intval', $ids);
    }

    /**
     * Post types this site maps to schema.org Event.
     *
     * @return string[]
     */
    private function eventPostTypes(): array
    {
        if ($this->eventPostTypes !== null) {
            return $this->eventPostTypes;
        }

        $count = (int) get_option('options_post_type_schema_types');
        $types = [];

        for ($i = 0; $i < $count; $i++) {
            if (get_option("options_post_type_schema_types_{$i}_schema_type") !== 'Event') {
                continue;
            }

            $postType = get_option("options_post_type_schema_types_{$i}_post_type");

            if (is_string($postType) && $postType !== '') {
                $types[] = $postType;
            }
        }

        return $this->eventPostTypes = $types;
    }

    private function isEventPost(\WP_Post $post): bool
    {
        return in_array($post->post_type, $this->eventPostTypes(), true);
    }

    /**
     * True when the Typesense Search plugin can talk to a configured server.
     */
    private function typesenseIsUsable(): bool
    {
        if (!class_exists(\TypesenseSearch\Services\SettingsRepository::class)) {
            return false;
        }

        return (new \TypesenseSearch\Services\SettingsRepository())->canUseTypesense();
    }
}
