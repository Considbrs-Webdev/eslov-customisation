<?php

/**
 * Restores the page navigation description field from municipio-extended.
 */

namespace EslovCustomisation\AcfFields;

class PageNavigationDescriptionField
{
    private const PARENT_GROUP = 'group_56d83cff12bb3';

    private const FIELD_KEY = 'field_page_navigation_description';

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
     * Register the navigation description textarea in Municipio's navigation settings group.
     */
    public function register(): void
    {
        if (!function_exists('acf_add_local_field') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (!acf_get_field_group(self::PARENT_GROUP)) {
            return;
        }

        if (function_exists('acf_get_field') && acf_get_field(self::FIELD_KEY)) {
            return;
        }

        acf_add_local_field([
            'parent' => self::PARENT_GROUP,
            'key' => self::FIELD_KEY,
            'label' => _x('Description', 'Page Navigation Field Label', 'eslov-customisation'),
            'name' => 'page_navigation_description',
            'type' => 'textarea',
            'instructions' => __(
                'A short description of the page to show in menus.',
                'eslov-customisation',
            ),
            'required' => 0,
            'rows' => 2,
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_56d83d2777785',
                        'operator' => '!=',
                        'value' => 1,
                    ],
                ],
            ],
            'graphql_field_name' => 'description',
            'show_in_graphql' => 1,
        ]);
    }
}
