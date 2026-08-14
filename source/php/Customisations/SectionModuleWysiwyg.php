<?php

namespace EslovCustomisation\Customisations;

/**
 * Restore the LTS full WYSIWYG editor on Modularity Sections text fields.
 *
 * Upstream modularity-sections 4.x ships split/featured and card Text as textarea
 * (full-width is already wysiwyg with the basic toolbar). Eslöv LTS
 * municipio-extended forced a full TinyMCE toolbar on all three so editors can
 * keep authoring HTML. Permanent site preference — not a data migration.
 *
 * Type is only switched in editor/save contexts. Frontend keeps the upstream
 * textarea formatting so stored HTML is not run through acf_the_content/wpautop.
 */
class SectionModuleWysiwyg
{
    private const SPLIT_FEATURED_TEXT = 'field_60d1a8040b829';

    private const CARD_TEXT = 'field_63ff1e7124e0e';

    private const FULL_TEXT = 'field_6154339333497';

    public function __construct()
    {
        add_filter('acf/load_field/key=' . self::SPLIT_FEATURED_TEXT, [$this, 'enableFullWysiwyg']);
        add_filter('acf/load_field/key=' . self::CARD_TEXT, [$this, 'enableFullWysiwyg']);
        add_filter('acf/load_field/key=' . self::FULL_TEXT, [$this, 'enableFullToolbar']);
    }

    /**
     * Change a textarea field to a full-toolbar WYSIWYG editor while editing or saving.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function enableFullWysiwyg(array $field): array
    {
        if (!$this->isEditingContext()) {
            return $field;
        }

        $field['type'] = 'wysiwyg';
        $field['toolbar'] = 'full';
        $field['tabs'] = $field['tabs'] ?? 'all';
        $field['media_upload'] = $field['media_upload'] ?? 1;
        $field['delay'] = $field['delay'] ?? 0;

        return $field;
    }

    /**
     * Keep an existing WYSIWYG field but use the full toolbar.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function enableFullToolbar(array $field): array
    {
        $field['toolbar'] = 'full';

        return $field;
    }

    /**
     * True in wp-admin, AJAX, and REST (Gutenberg / ACF block saves).
     */
    private function isEditingContext(): bool
    {
        if (is_admin() || wp_doing_ajax()) {
            return true;
        }

        return defined('REST_REQUEST') && REST_REQUEST;
    }
}
