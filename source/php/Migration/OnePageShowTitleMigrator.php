<?php

namespace EslovCustomisation\Migration;

class OnePageShowTitleMigrator
{
    public const META_KEY = 'post_one_page_show_title';

    public const ACF_FIELD_KEY = 'field_64f8759c6a2a2';

    private const TEMPLATE = 'one-page.blade.php';

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $force = false,
        private readonly ?int $postId = null,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        foreach ($this->getPostIds() as $postId) {
            $current = get_post_meta($postId, self::META_KEY, true);

            if ($this->isEnabled($current) && !$this->force) {
                $result->skipped++;
                continue;
            }

            if (!$this->dryRun) {
                if (function_exists('update_field')) {
                    update_field(self::META_KEY, 1, $postId);
                } else {
                    update_post_meta($postId, self::META_KEY, '1');
                    update_post_meta($postId, '_' . self::META_KEY, self::ACF_FIELD_KEY);
                }
            }

            $result->migrated++;
        }

        if ($result->migrated > 0) {
            $result->addMessage(sprintf(
                '%s one-page title on %d post(s).',
                $this->dryRun ? 'Would enable' : 'Enabled',
                $result->migrated,
            ));
        }

        if ($result->migrated === 0 && $result->skipped === 0) {
            $result->skipped = 1;
            $result->addMessage('No one-page posts found.');
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function getPostIds(): array
    {
        if ($this->postId !== null) {
            $template = (string) get_post_meta($this->postId, '_wp_page_template', true);

            if ($template !== self::TEMPLATE) {
                return [];
            }

            return [$this->postId];
        }

        $query = new \WP_Query([
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'post_status'            => 'any',
            'post_type'              => 'page',
            'posts_per_page'         => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'   => '_wp_page_template',
                    'value' => self::TEMPLATE,
                ],
            ],
        ]);

        return array_map('intval', $query->posts);
    }

    private function isEnabled(mixed $value): bool
    {
        return $value === '1' || $value === 1 || $value === true;
    }
}
