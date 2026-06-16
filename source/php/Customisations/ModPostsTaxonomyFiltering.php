<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\PostsFiltering\FilterRulesResolver;
use EslovCustomisation\PostsFiltering\TaxQueryBuilder;
use Modularity\Module\Posts\Posts;

/**
 * Runtime query and archive-link filtering for Eslöv mod-posts taxonomy repeater.
 */
class ModPostsTaxonomyFiltering
{
    private static ?int $contextModuleId = null;

    public function __construct()
    {
        add_filter('acf/load_value/name=posts_taxonomy_filter', [$this, 'captureModuleContext'], 1, 3);
        add_filter('Modularity/Module/Posts/GetPosts/Args', [$this, 'applyTaxQuery'], 10, 4);
        add_filter('Modularity/Module/Posts/template', [$this, 'applyArchiveFilters'], 5, 4);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function captureModuleContext(mixed $value, int|string $postId, array $field): mixed
    {
        if (is_numeric($postId) && get_post_type((int) $postId) === 'mod-posts') {
            self::$contextModuleId = (int) $postId;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $fields
     * @param array<int, int> $stickyPostIds
     * @return array<string, mixed>
     */
    public function applyTaxQuery(array $args, array $fields, int $page, array $stickyPostIds): array
    {
        $rules = FilterRulesResolver::fromFields($fields, self::$contextModuleId);

        if ($rules === []) {
            return $args;
        }

        unset($args['tax_query']);
        $args['tax_query'] = TaxQueryBuilder::build($rules);

        return $args;
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function applyArchiveFilters(
        string $template,
        Posts $module,
        array $data,
        array $fields
    ): string {
        $modulePostId = isset($module->ID) ? (int) $module->ID : self::$contextModuleId;
        $archiveFilters = FilterRulesResolver::archiveFiltersFromFields($fields, $modulePostId);

        if ($archiveFilters === []) {
            return $template;
        }

        $module->data['filters'] = $archiveFilters;

        $archiveLinkUrl = $module->data['archiveLinkUrl'] ?? null;

        if (!is_string($archiveLinkUrl) || $archiveLinkUrl === '') {
            return $template;
        }

        $baseUrl = strtok($archiveLinkUrl, '?');

        if (!is_string($baseUrl) || $baseUrl === '') {
            return $template;
        }

        $module->data['archiveLinkUrl'] = $baseUrl . '?' . http_build_query($archiveFilters);

        return $template;
    }
}
