<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations\ExternalContent;

use EslovCustomisation\Sites;
use Municipio\SchemaData\ExternalContent\Rest\AjaxSync;

/**
 * Hold externally synced Event posts for review on subsites.
 *
 * Municipio External Content always inserts as publish. On subsites, new posts
 * and posts that change after they were published are stored as pending instead.
 * Unchanged posts are skipped by Municipio checksum and keep their status.
 *
 * Applies to WP-Cron (`Municipio/ExternalContent/Sync`) and the admin button
 * (`wp_ajax_municipio_external_content_sync`), which calls SyncHandler directly.
 *
 * Toggle with ESLOV_EXTERNAL_CONTENT_SUBSITE_REVIEW. Only post types mapped to
 * schema type Event (Settings → Post type schema settings) are affected.
 */
class SubsiteImportReview
{
    private const REVIEW_STATUS = 'pending';

    private const SCHEMA_TYPE_EVENT = 'Event';

    private const SCHEMA_SETTINGS_OPTION = 'options_post_type_schema_types';

    private const SYNC_START_PRIORITY = 2;

    private const SYNC_END_PRIORITY = 20;

    private const AJAX_SYNC_START_PRIORITY = 1;

    private const CAPABILITY_FILTER_PRIORITY = 20;

    private bool $syncing = false;

    /**
     * @var array<string, string>|null
     */
    private ?array $schemaTypeByPostType = null;

    public function __construct()
    {
        if (!$this->isEnabled()) {
            return;
        }

        add_action('Municipio/ExternalContent/Sync', [$this, 'startSync'], self::SYNC_START_PRIORITY);
        add_action('Municipio/ExternalContent/Sync', [$this, 'endSync'], self::SYNC_END_PRIORITY);
        add_action('wp_ajax_' . $this->ajaxAction(), [$this, 'startSync'], self::AJAX_SYNC_START_PRIORITY);
        add_action('shutdown', [$this, 'endSync']);
        add_filter('wp_insert_post_data', [$this, 'setReviewStatus'], 10, 4);
        add_filter('register_post_type_args', [$this, 'allowReviewCapabilities'], self::CAPABILITY_FILTER_PRIORITY, 2);
    }

    /**
     * Mark that an External Content sync is in progress.
     */
    public function startSync(): void
    {
        $this->syncing = true;
    }

    /**
     * Clear the in-progress sync flag.
     */
    public function endSync(): void
    {
        $this->syncing = false;
    }

    /**
     * Force pending status for new and previously published synced posts on subsites.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $postarr
     * @param array<string, mixed> $unsanitizedPostarr
     * @param bool                 $update
     * @return array<string, mixed>
     */
    public function setReviewStatus(array $data, array $postarr, array $unsanitizedPostarr = [], bool $update = false): array
    {
        if (!$this->shouldModerateInsert($data)) {
            return $data;
        }

        $postId = (int) ($postarr['ID'] ?? 0);
        $isUpdate = $update || $postId > 0;

        if (!$isUpdate) {
            $data['post_status'] = $this->reviewStatus();

            return $data;
        }

        $currentStatus = $postId > 0 ? get_post_status($postId) : false;

        if ($currentStatus === false || $currentStatus === 'publish') {
            $data['post_status'] = $this->reviewStatus();

            return $data;
        }

        $data['post_status'] = $currentStatus;

        return $data;
    }

    /**
     * Let editors review synced Event post types on subsites. Creating posts stays locked.
     *
     * @param array<string, mixed> $args
     */
    public function allowReviewCapabilities(array $args, string $postType): array
    {
        if (!Sites::isSubsite()) {
            return $args;
        }

        if (!$this->isEventSchemaPostType($postType)) {
            return $args;
        }

        $capabilities = $args['capabilities'] ?? [];
        if (($capabilities['edit_post'] ?? '') !== 'do_not_allow') {
            return $args;
        }

        $args['capabilities'] = [
            'create_posts' => 'do_not_allow',
        ];

        return $args;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function shouldModerateInsert(array $data): bool
    {
        if (!$this->isExternalContentSync()) {
            return false;
        }

        if (!Sites::isSubsite()) {
            return false;
        }

        $postType = $data['post_type'] ?? '';
        if ($postType === 'attachment' || $postType === 'revision') {
            return false;
        }

        if (!$this->isEventSchemaPostType(is_string($postType) ? $postType : '')) {
            return false;
        }

        $postStatus = $data['post_status'] ?? '';

        return $postStatus !== 'inherit';
    }

    private function isEnabled(): bool
    {
        return defined('ESLOV_EXTERNAL_CONTENT_SUBSITE_REVIEW')
            && ESLOV_EXTERNAL_CONTENT_SUBSITE_REVIEW === true;
    }

    private function isEventSchemaPostType(string $postType): bool
    {
        if ($postType === '') {
            return false;
        }

        return ($this->schemaTypesByPostType()[$postType] ?? null) === self::SCHEMA_TYPE_EVENT;
    }

    /**
     * Schema type per post type from Settings → Post type schema settings.
     *
     * @return array<string, string>
     */
    private function schemaTypesByPostType(): array
    {
        if ($this->schemaTypeByPostType !== null) {
            return $this->schemaTypeByPostType;
        }

        $this->schemaTypeByPostType = [];
        $rowCount = (int) get_option(self::SCHEMA_SETTINGS_OPTION, 0);

        if ($rowCount < 1) {
            return $this->schemaTypeByPostType;
        }

        foreach (range(0, $rowCount - 1) as $index) {
            $mappedPostType = get_option(self::SCHEMA_SETTINGS_OPTION . "_{$index}_post_type", '');
            $schemaType = get_option(self::SCHEMA_SETTINGS_OPTION . "_{$index}_schema_type", '');

            if (!is_string($mappedPostType) || $mappedPostType === '' || !is_string($schemaType) || $schemaType === '') {
                continue;
            }

            $this->schemaTypeByPostType[$mappedPostType] = $schemaType;
        }

        return $this->schemaTypeByPostType;
    }

    private function isExternalContentSync(): bool
    {
        if ($this->syncing) {
            return true;
        }

        return $this->isAjaxExternalContentSync();
    }

    private function isAjaxExternalContentSync(): bool
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            return false;
        }

        $action = $_REQUEST['action'] ?? '';

        return $action === $this->ajaxAction();
    }

    private function ajaxAction(): string
    {
        if (class_exists(AjaxSync::class)) {
            return AjaxSync::$action;
        }

        return 'municipio_external_content_sync';
    }

    private function reviewStatus(): string
    {
        $status = apply_filters(
            'EslovCustomisation/ExternalContent/subsiteReviewStatus',
            self::REVIEW_STATUS
        );

        if (!is_string($status) || $status === '') {
            return self::REVIEW_STATUS;
        }

        return $status;
    }
}
