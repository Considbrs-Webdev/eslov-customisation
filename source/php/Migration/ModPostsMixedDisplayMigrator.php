<?php

namespace EslovCustomisation\Migration;

class ModPostsMixedDisplayMigrator
{
    private const LEGACY_DISPLAY_VALUE = 'mixed';

    private const TARGET_DISPLAY_VALUE = 'index';

    private const TARGET_COLUMNS_VALUE = 'grid-md-4';

    /** @var array<string, string> */
    private const META_FIELD_KEYS = [
        'posts_display_as' => 'field_571dfd4c0d9d9',
        'posts_display_as_conditional' => 'field_6762ecffda0e3',
        'show_as_slider' => 'field_6356477fbc5e4',
        'posts_columns' => 'field_571dfdf50d9da',
    ];

    public function __construct(
        private bool $dryRun = false,
        private ?int $postId = null,
        private bool $force = false,
    ) {}

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();
        $moduleIds = $this->postId !== null ? [$this->postId] : $this->findCandidateModuleIds();

        foreach ($moduleIds as $moduleId) {
            $this->migrateModule($moduleId, $result);
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function findCandidateModuleIds(): array
    {
        global $wpdb;

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = 'posts_display_as'
            AND pm.meta_value = %s
            AND p.post_type = 'mod-posts'",
            self::LEGACY_DISPLAY_VALUE
        ));

        return array_map('intval', $rows ?: []);
    }

    private function migrateModule(int $moduleId, MigrationResult $result): void
    {
        if (get_post_type($moduleId) !== 'mod-posts') {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: not mod-posts");

            return;
        }

        $currentDisplay = (string) get_post_meta($moduleId, 'posts_display_as', true);
        $currentSlider = (string) get_post_meta($moduleId, 'show_as_slider', true);

        if ($currentDisplay !== self::LEGACY_DISPLAY_VALUE && !$this->force) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: posts_display_as is not mixed");

            return;
        }

        if (
            $currentDisplay === self::TARGET_DISPLAY_VALUE
            && $currentSlider === '1'
            && !$this->force
        ) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: already Card + slider");

            return;
        }

        if ($this->dryRun) {
            $result->migrated++;
            $result->addMessage(sprintf(
                'Would migrate mod-posts %d (%s): mixed → index + slider + 3 columns',
                $moduleId,
                get_the_title($moduleId)
            ));

            return;
        }

        $this->writeTargetMeta($moduleId);

        $result->migrated++;
        $result->addMessage(sprintf(
            'Migrated mod-posts %d (%s): mixed → index + slider + 3 columns',
            $moduleId,
            get_the_title($moduleId)
        ));
    }

    private function writeTargetMeta(int $moduleId): void
    {
        update_post_meta($moduleId, 'posts_display_as', self::TARGET_DISPLAY_VALUE);
        update_post_meta($moduleId, '_posts_display_as', self::META_FIELD_KEYS['posts_display_as']);

        update_post_meta($moduleId, 'posts_display_as_conditional', self::TARGET_DISPLAY_VALUE);
        update_post_meta(
            $moduleId,
            '_posts_display_as_conditional',
            self::META_FIELD_KEYS['posts_display_as_conditional']
        );

        update_post_meta($moduleId, 'show_as_slider', '1');
        update_post_meta($moduleId, '_show_as_slider', self::META_FIELD_KEYS['show_as_slider']);

        update_post_meta($moduleId, 'posts_columns', self::TARGET_COLUMNS_VALUE);
        update_post_meta($moduleId, '_posts_columns', self::META_FIELD_KEYS['posts_columns']);
    }
}
