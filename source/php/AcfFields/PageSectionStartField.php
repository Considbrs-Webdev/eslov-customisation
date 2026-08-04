<?php

/**
 * Registers the "Section start page" ACF field into Municipio's Page settings group.
 */

namespace EslovCustomisation\AcfFields;

class PageSectionStartField
{
    private const PARENT_GROUP = 'group_56d83cff12bb3';

    private const FIELD_KEY = 'field_page_navigation_section_start_page';

    public function __construct()
    {
        add_action('acf/init', function () {
            add_action('init', [$this, 'register'], 20);
            if (did_action('init')) {
                $this->register();
            }
        });
    }

    /**
     * Register the section start page true_false field into the existing page navigation group.
     */
    public function register(): void
    {
        if (!function_exists('acf_add_local_field') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (!acf_get_field_group(self::PARENT_GROUP)) {
            return;
        }

        acf_add_local_field([
            'parent' => self::PARENT_GROUP,
            'key' => self::FIELD_KEY,
            'label' => __('Section start page', 'eslov-customisation'),
            'name' => 'page_navigation_section_start_page',
            'type' => 'true_false',
            'instructions' => __('Mark this page as a section start page. Child pages will display a link back to this page.', 'eslov-customisation'),
            'required' => 0,
            'default_value' => 0,
            'ui' => 1,
            'graphql_field_name' => 'sectionStartPage',
        ]);
    }
}
