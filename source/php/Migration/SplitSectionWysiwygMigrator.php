<?php

/**
 * Migrates Split Section textarea content to WYSIWYG field.
 */

namespace EslovCustomisation\Migration;

class SplitSectionWysiwygMigrator
{
    public function __construct(
        private bool $dryRun = false,
        private ?int $postId = null,
    ) {
    }

    public function migrate(): MigrationResult
    {
        $result = new MigrationResult();

        $posts = $this->getPosts();

        if (empty($posts)) {
            $result->addMessage('No mod-section-split posts found.');

            return $result;
        }

        $result->addMessage(sprintf('Found %d mod-section-split post(s).', count($posts)));

        foreach ($posts as $post) {
            $this->migratePost($post, $result);
        }

        return $result;
    }

    /**
     * @return \WP_Post[]
     */
    private function getPosts(): array
    {
        $args = [
            'post_type'      => 'mod-section-split',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ];

        if ($this->postId !== null) {
            $args['p'] = $this->postId;
        }

        return get_posts($args);
    }

    private function migratePost(\WP_Post $post, MigrationResult $result): void
    {
        $textContent = get_field('text', $post->ID);

        if (empty($textContent)) {
            $result->addMessage(sprintf('  [%d] %s — skipped (no text content)', $post->ID, $post->post_title));
            $result->skipped++;

            return;
        }

        $existingWysiwyg = get_field('wysiwyg_text', $post->ID);

        if (!empty($existingWysiwyg)) {
            $result->addMessage(sprintf('  [%d] %s — skipped (wysiwyg_text already has content)', $post->ID, $post->post_title));
            $result->skipped++;

            return;
        }

        if ($this->dryRun) {
            $result->addMessage(sprintf('  [%d] %s — would migrate', $post->ID, $post->post_title));
            $result->migrated++;

            return;
        }

        $updateWysiwyg = update_field('wysiwyg_text', $textContent, $post->ID);
        $updateToggle = update_field('use_wysiwyg', true, $post->ID);

        if ($updateWysiwyg === false || $updateToggle === false) {
            $result->addMessage(sprintf('  [%d] %s — ERROR updating fields', $post->ID, $post->post_title));
            $result->errors++;

            return;
        }

        $result->addMessage(sprintf('  [%d] %s — migrated', $post->ID, $post->post_title));
        $result->migrated++;
    }
}
