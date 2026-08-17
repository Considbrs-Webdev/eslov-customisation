<?php

namespace EslovCustomisation\Migration;

/**
 * Persist paragraph markup in section Text where LTS relied on display wpautop.
 *
 * Split/featured/card stay textarea on the frontend (see SectionModuleWysiwyg),
 * so blank lines collapse unless they are stored as <p>. Full-width is already
 * wysiwyg on the frontend and is left alone.
 *
 * Only runs when the value contains a blank line (the Visual editor's
 * paragraph break). Does not wpautop single-newline HTML such as lists.
 */
class SectionTextAutopMigrator
{
    public const META_TEXT = 'text';

    /**
     * @var array<string, string>
     */
    private const FIELD_KEYS = [
        'mod-section-split' => 'field_60d1a8040b829',
        'mod-section-featured' => 'field_60d1a8040b829',
        'mod-section-card' => 'field_63ff1e7124e0e',
    ];

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly ?int $postId = null,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        foreach ($this->getPosts() as $post) {
            $fieldKey = self::FIELD_KEYS[$post->post_type] ?? null;

            if ($fieldKey === null) {
                $result->skipped++;
                continue;
            }

            $current = (string) get_post_meta($post->ID, self::META_TEXT, true);
            $transformed = self::transform($current);

            if ($transformed === null) {
                $result->skipped++;
                continue;
            }

            if (!$this->dryRun) {
                $this->writeField($post->ID, $fieldKey, $transformed);
            }

            $result->migrated++;
        }

        if ($result->migrated > 0) {
            $result->addMessage(sprintf(
                '%s <p> markup on %d section module(s).',
                $this->dryRun ? 'Would persist' : 'Persisted',
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
     * @return string|null Transformed HTML, or null when the value should be left alone.
     */
    public static function transform(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        if (!self::hasBlankLine($text)) {
            return null;
        }

        $autop = wpautop($text);

        if ($autop === $text) {
            return null;
        }

        return $autop;
    }

    public static function hasBlankLine(string $text): bool
    {
        return (bool) preg_match('/\r?\n\s*\r?\n/', $text);
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

    private function writeField(int $postId, string $fieldKey, string $value): void
    {
        if (function_exists('update_field')) {
            update_field($fieldKey, $value, $postId);

            return;
        }

        update_post_meta($postId, self::META_TEXT, $value);
        update_post_meta($postId, '_' . self::META_TEXT, $fieldKey);
    }
}
