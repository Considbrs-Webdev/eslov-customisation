<?php

namespace EslovCustomisation\Migration;

class ModPostsTaxonomyDisplayMigrator
{
    private const LEGACY_META_KEY = 'taxonomy_selection_in_fields';

    private const TARGET_META_KEY = 'taxonomy_display';

    private const TARGET_FIELD_KEY = 'field_630645dcff161';

    /** @var string[] */
    private const LEGACY_META_KEYS_TO_REMOVE = [
        'taxonomy_selection_in_fields',
        '_taxonomy_selection_in_fields',
        'show_taxonomies_slider',
        '_show_taxonomies_slider',
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
            "SELECT DISTINCT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
            AND meta_value NOT IN ('', 'a:0:{}')",
            self::LEGACY_META_KEY
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

        $legacyTaxonomies = $this->normalizeTaxonomyList(get_post_meta($moduleId, self::LEGACY_META_KEY, true));

        if ($legacyTaxonomies === []) {
            $result->skipped++;
            $result->addMessage("Skipped {$moduleId}: empty legacy taxonomy list");

            return;
        }

        $existingTaxonomies = $this->normalizeTaxonomyList(get_post_meta($moduleId, self::TARGET_META_KEY, true));

        if ($existingTaxonomies !== [] && !$this->force) {
            if ($existingTaxonomies === $legacyTaxonomies) {
                $result->skipped++;
                $result->addMessage("Skipped {$moduleId}: taxonomy_display already matches legacy");

                return;
            }

            $result->skipped++;
            $result->addMessage(
                "Skipped {$moduleId}: taxonomy_display already set (use --force to overwrite)"
            );

            return;
        }

        if ($this->dryRun) {
            $result->migrated++;
            $result->addMessage(sprintf(
                'Would migrate mod-posts %d (%s): %s → taxonomy_display',
                $moduleId,
                get_the_title($moduleId),
                implode(', ', $legacyTaxonomies)
            ));

            return;
        }

        update_post_meta($moduleId, self::TARGET_META_KEY, $legacyTaxonomies);
        update_post_meta($moduleId, '_' . self::TARGET_META_KEY, self::TARGET_FIELD_KEY);

        foreach (self::LEGACY_META_KEYS_TO_REMOVE as $metaKey) {
            delete_post_meta($moduleId, $metaKey);
        }

        $result->migrated++;
        $result->addMessage(sprintf(
            'Migrated mod-posts %d (%s): %s',
            $moduleId,
            get_the_title($moduleId),
            implode(', ', $legacyTaxonomies)
        ));
    }

    /**
     * @return string[]
     */
    private function normalizeTaxonomyList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $taxonomies = array_values(array_filter(array_map(
            static fn ($taxonomy) => is_string($taxonomy) ? $taxonomy : '',
            $value
        )));

        sort($taxonomies);

        return $taxonomies;
    }
}
