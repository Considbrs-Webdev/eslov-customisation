<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\AcfFields\CategoryHideInPublicFormField;
use ModularityFrontendForm\FieldMapping\Mapper\Acf\TaxonomyFieldMapper;

/**
 * Removes categories flagged "Hide in public form" from the term choices that
 * modularity-frontend-form builds for taxonomy fields. The form plugin has no
 * filter for this, so we narrow get_terms() to calls made by its TaxonomyFieldMapper
 * (get_terms is used all over the site and must stay untouched elsewhere).
 */
class HideInternalCategoriesFromPublicForm
{
    public function __construct()
    {
        // Plugin load order is unknown here, so wait until modularity-frontend-form had a chance to load.
        add_action('plugins_loaded', function () {
            if (defined('MODULARITYFRONTENDFORM_PATH')) {
                add_filter('get_terms_args', [$this, 'excludeHiddenTerms'], 10, 2);
            }
        });
    }

    /**
     * @param array<string, mixed> $args
     * @param string[] $taxonomies
     * @return array<string, mixed>
     */
    public function excludeHiddenTerms(array $args, array $taxonomies): array
    {
        if (is_admin() || !in_array('category', $taxonomies, true) || !$this->calledByFormMapper()) {
            return $args;
        }

        $args['meta_query'] = array_merge(
            (array) ($args['meta_query'] ?? []),
            [[
                'relation' => 'OR',
                ['key' => CategoryHideInPublicFormField::FIELD_NAME, 'compare' => 'NOT EXISTS'],
                ['key' => CategoryHideInPublicFormField::FIELD_NAME, 'value' => '1', 'compare' => '!='],
            ]],
        );

        return $args;
    }

    private function calledByFormMapper(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            if (($frame['class'] ?? null) === TaxonomyFieldMapper::class) {
                return true;
            }
        }

        return false;
    }
}
