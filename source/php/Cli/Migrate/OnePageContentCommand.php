<?php

namespace EslovCustomisation\Cli\Migrate;

use EslovCustomisation\Migration\OnePageContentConverter;
use WP_CLI;
use WP_CLI\Utils;
use WP_Post;

/**
 * Migrates legacy classic content on Municipio one-page templates to Gutenberg blocks.
 */
class OnePageContentCommand
{
    private const TEMPLATE = 'one-page.blade.php';

    private const CHECKSUM_META = '_eslov_one_page_content_migrated_sha1';

    private const MIGRATED_AT_META = '_eslov_one_page_content_migrated_at';

    private OnePageContentConverter $converter;

    public function __construct()
    {
        $this->converter = new OnePageContentConverter();
    }

    /**
     * Converts legacy one-page post_content to block markup.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Report what would change without writing to the database.
     *
     * [--post-id=<id>]
     * : Limit migration to a single post on the current site.
     *
     * [--network]
     * : Run across all sites in the multisite network.
     *
     * [--fallback=<mode>]
     * : Fallback behavior for ambiguous fragments. Currently only "freeform" is supported.
     * ---
     * default: freeform
     * options:
     *   - freeform
     * ---
     *
     * [--fail-on-fallback]
     * : Skip posts whose converted output requires one or more freeform fallbacks.
     *
     * [--report=<format>]
     * : Report format.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp eslov migrate one-page-content --dry-run
     *     wp eslov migrate one-page-content --post-id=588812
     *     wp eslov migrate one-page-content --network --dry-run --report=json
     *
     * @param array<int,string> $args
     * @param array<string,mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $dryRun         = Utils\get_flag_value($assocArgs, 'dry-run', false);
        $network        = Utils\get_flag_value($assocArgs, 'network', false);
        $postId         = isset($assocArgs['post-id']) ? (int) $assocArgs['post-id'] : 0;
        $fallback       = (string) Utils\get_flag_value($assocArgs, 'fallback', 'freeform');
        $failOnFallback = Utils\get_flag_value($assocArgs, 'fail-on-fallback', false);
        $reportFormat   = (string) Utils\get_flag_value($assocArgs, 'report', 'table');

        if ($fallback !== 'freeform') {
            WP_CLI::error('Only --fallback=freeform is currently supported.');
        }

        if (!in_array($reportFormat, ['table', 'json'], true)) {
            WP_CLI::error('Invalid --report value. Use "table" or "json".');
        }

        $siteIds = $network && is_multisite()
            ? array_map(static fn($site): int => (int) $site->blog_id, get_sites(['fields' => 'all', 'number' => 0]))
            : [get_current_blog_id()];

        $summary = $this->emptySummary($dryRun);
        $rows    = [];

        foreach ($siteIds as $siteId) {
            if (is_multisite()) {
                switch_to_blog($siteId);
            }

            try {
                $posts = $this->getPosts($postId);

                foreach ($posts as $post) {
                    $row = $this->processPost($post, $siteId, $dryRun, $failOnFallback);
                    $rows[] = $row;
                    $this->addRowToSummary($summary, $row);

                    if ($reportFormat === 'table' && $this->shouldPrintRow($row)) {
                        $this->logRow($row);
                    }
                }
            } finally {
                if (is_multisite()) {
                    restore_current_blog();
                }
            }
        }

        if ($reportFormat === 'json') {
            WP_CLI::line((string) wp_json_encode([
                'summary' => $summary,
                'rows'    => $rows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            WP_CLI::line('');
            WP_CLI::line(sprintf(
                '%s scanned=%d converted=%d would_convert=%d skipped=%d failed=%d warnings=%d freeform=%d',
                $dryRun ? 'Dry run complete:' : 'Migration complete:',
                $summary['scanned'],
                $summary['converted'],
                $summary['would_convert'],
                $summary['skipped'],
                $summary['failed'],
                $summary['warnings'],
                $summary['freeform']
            ));
        }

        if ($summary['failed'] > 0) {
            WP_CLI::warning('Some posts failed conversion. Re-run with --report=json for details.');
        }
    }

    /**
     * @return array<int,WP_Post>
     */
    private function getPosts(int $postId): array
    {
        if ($postId > 0) {
            $post = get_post($postId);

            if (!$post instanceof WP_Post) {
                WP_CLI::error(sprintf('Post %d was not found on site %d.', $postId, get_current_blog_id()));
            }

            $template = (string) get_post_meta($postId, '_wp_page_template', true);

            if ($template !== self::TEMPLATE) {
                WP_CLI::error(sprintf('Post %d does not use %s.', $postId, self::TEMPLATE));
            }

            return [$post];
        }

        $query = new \WP_Query([
            'fields'                 => 'all',
            'no_found_rows'          => true,
            'post_status'            => 'publish',
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

        return array_values(array_filter($query->posts, static fn($post): bool => $post instanceof WP_Post));
    }

    /**
     * @return array<string,mixed>
     */
    private function processPost(WP_Post $post, int $siteId, bool $dryRun, bool $failOnFallback): array
    {
        $content = (string) $post->post_content;
        $row = [
            'site_id'  => $siteId,
            'post_id'  => $post->ID,
            'title'    => get_the_title($post),
            'status'   => '',
            'warnings' => [],
            'stats'    => [],
            'classify' => $this->converter->classify($content),
        ];

        if (trim($content) === '') {
            $row['status'] = 'skipped_empty';
            return $row;
        }

        if (has_blocks($content)) {
            $row['status'] = 'skipped_blocks';
            return $row;
        }

        $checksum = sha1($content);
        $previous = (string) get_post_meta($post->ID, self::CHECKSUM_META, true);

        if ($previous === $checksum) {
            $row['status'] = 'skipped_checksum';
            return $row;
        }

        $conversion = $this->converter->convert($content);
        $row['warnings'] = $conversion['warnings'];
        $row['stats']    = $conversion['stats'];

        if ($conversion['status'] === 'junk') {
            $row['status'] = 'skipped_junk';
            return $row;
        }

        if ($conversion['status'] === 'failed') {
            $row['status'] = 'failed';
            return $row;
        }

        if ($failOnFallback && ($conversion['stats']['freeform'] ?? 0) > 0) {
            $row['status'] = 'skipped_freeform';
            $row['warnings'][] = 'Skipped because --fail-on-fallback was provided and conversion requires freeform.';
            return $row;
        }

        $rendered = apply_filters('the_content', $conversion['content']);

        if (trim(wp_strip_all_tags((string) $rendered)) === '' && trim(wp_strip_all_tags($content)) !== '') {
            $row['status'] = 'failed';
            $row['warnings'][] = 'Rendered converted content is empty while source content had text.';
            return $row;
        }

        if ($dryRun) {
            $row['status'] = 'would_convert';
            return $row;
        }

        $updated = wp_update_post([
            'ID'           => $post->ID,
            'post_content' => $conversion['content'],
        ], true);

        if (is_wp_error($updated)) {
            $row['status'] = 'failed';
            $row['warnings'][] = $updated->get_error_message();
            return $row;
        }

        update_post_meta($post->ID, self::CHECKSUM_META, $checksum);
        update_post_meta($post->ID, self::MIGRATED_AT_META, gmdate('c'));

        $row['status'] = 'converted';
        return $row;
    }

    /**
     * @return array<string,mixed>
     */
    private function emptySummary(bool $dryRun): array
    {
        return [
            'dry_run'       => $dryRun,
            'scanned'       => 0,
            'converted'     => 0,
            'would_convert' => 0,
            'skipped'       => 0,
            'failed'        => 0,
            'warnings'      => 0,
            'freeform'      => 0,
            'statuses'      => [],
        ];
    }

    /**
     * @param array<string,mixed> $summary
     * @param array<string,mixed> $row
     */
    private function addRowToSummary(array &$summary, array $row): void
    {
        $status = (string) $row['status'];

        $summary['scanned']++;
        $summary['statuses'][$status] = ($summary['statuses'][$status] ?? 0) + 1;
        $summary['warnings'] += count($row['warnings'] ?? []);
        $summary['freeform'] += (int) ($row['stats']['freeform'] ?? 0);

        if ($status === 'converted') {
            $summary['converted']++;
        } elseif ($status === 'would_convert') {
            $summary['would_convert']++;
        } elseif ($status === 'failed') {
            $summary['failed']++;
        } elseif (str_starts_with($status, 'skipped_')) {
            $summary['skipped']++;
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private function shouldPrintRow(array $row): bool
    {
        if (in_array($row['status'], ['skipped_empty', 'skipped_blocks'], true)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function logRow(array $row): void
    {
        $stats = $row['stats'] ?? [];
        $parts = [];

        foreach (['paragraph', 'heading', 'more', 'list', 'shortcode', 'button', 'image', 'freeform'] as $key) {
            if (!empty($stats[$key])) {
                $parts[] = $key . '=' . $stats[$key];
            }
        }

        $message = sprintf(
            '[%s] site=%d post=%d "%s" %s',
            $row['status'],
            $row['site_id'],
            $row['post_id'],
            $row['title'],
            implode(' ', $parts)
        );

        if (!empty($row['warnings'])) {
            $message .= ' warnings=' . implode(' | ', $row['warnings']);
        }

        WP_CLI::line($message);
    }
}
