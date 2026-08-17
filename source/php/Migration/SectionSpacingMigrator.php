<?php

namespace EslovCustomisation\Migration;

/**
 * Write Municipio Spacing Top/Bottom defaults where LTS never stored the keys.
 *
 * Does not read or change module_layout_remove_spacing_below (wrapper gap).
 */
class SectionSpacingMigrator
{
    public const META_SPACING_TOP = 'spacing_top';

    public const META_SPACING_BOTTOM = 'spacing_bottom';

    /**
     * @var array<string, array{top: string, bottom: string}>
     */
    private const FIELD_KEYS = [
        'mod-section-split' => [
            'top' => 'field_60d2f7b110b0b',
            'bottom' => 'field_60d2f7cc10b0c',
        ],
        'mod-section-featured' => [
            'top' => 'field_60d2f7b110b0b',
            'bottom' => 'field_60d2f7cc10b0c',
        ],
        'mod-section-full' => [
            'top' => 'field_61543393334c7',
            'bottom' => 'field_61543393334cc',
        ],
        'mod-section-card' => [
            'top' => 'field_63ff205324e11',
            'bottom' => 'field_63ff207624e12',
        ],
    ];

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $force = false,
        private readonly ?int $postId = null,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        foreach ($this->getPosts() as $post) {
            $keys = self::FIELD_KEYS[$post->post_type] ?? null;

            if ($keys === null) {
                $result->skipped++;
                continue;
            }

            $wrote = false;

            if ($this->ensureDefault($post->ID, self::META_SPACING_TOP, $keys['top'])) {
                $wrote = true;
            }

            if ($this->ensureDefault($post->ID, self::META_SPACING_BOTTOM, $keys['bottom'])) {
                $wrote = true;
            }

            if ($wrote) {
                $result->migrated++;
            } else {
                $result->skipped++;
            }
        }

        if ($result->migrated > 0) {
            $result->addMessage(sprintf(
                '%s spacing_top/spacing_bottom=1 on %d section module(s).',
                $this->dryRun ? 'Would set' : 'Set',
                $result->migrated,
            ));
        }

        if ($result->migrated === 0 && $result->skipped === 0) {
            $result->skipped = 1;
            $result->addMessage('No section modules found.');
        }

        return $result;
    }

    /**
     * @return \WP_Post[]
     */
    private function getPosts(): array
    {
        $postTypes = array_keys(self::FIELD_KEYS);

        if ($this->postId !== null) {
            $post = get_post($this->postId);

            if (!$post instanceof \WP_Post || !in_array($post->post_type, $postTypes, true)) {
                return [];
            }

            return [$post];
        }

        return get_posts([
            'post_type' => $postTypes,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'suppress_filters' => true,
        ]);
    }

    private function ensureDefault(int $postId, string $metaKey, string $fieldKey): bool
    {
        $current = get_post_meta($postId, $metaKey, true);

        if ($this->hasExplicitValue($current) && !$this->force) {
            return false;
        }

        if (!$this->dryRun) {
            if (function_exists('update_field')) {
                update_field($metaKey, 1, $postId);
            } else {
                update_post_meta($postId, $metaKey, '1');
                update_post_meta($postId, '_' . $metaKey, $fieldKey);
            }
        }

        return true;
    }

    private function hasExplicitValue(mixed $value): bool
    {
        return $value === '0'
            || $value === '1'
            || $value === 0
            || $value === 1
            || $value === true
            || $value === false;
    }
}
