<?php

namespace EslovCustomisation\AcfFields;

/**
 * "Hide in public form" toggle on category terms. Read by
 * Customisations\HideInternalCategoriesFromPublicForm.
 */
class CategoryHideInPublicFormField
{
    public const FIELD_NAME = 'hide_in_public_form';

    public function __construct()
    {
        add_action('acf/init', [$this, 'register'], 5);
    }

    public function register(): void
    {
        if (!function_exists('acf_add_local_field_group') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (!defined('MODULARITYFRONTENDFORM_PATH')) {
            return;
        }

        if (acf_get_field_group('group_category_hide_in_public_form')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_category_hide_in_public_form',
            'title' => __('Public form', 'eslov-customisation'),
            'fields' => [
                [
                    'key' => 'field_category_hide_in_public_form',
                    'label' => __('Hide in public form', 'eslov-customisation'),
                    'name' => self::FIELD_NAME,
                    'type' => 'true_false',
                    'instructions' => __(
                        'Internal category: not offered to visitors submitting through the public form.',
                        'eslov-customisation',
                    ),
                    'ui' => 1,
                    'default_value' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'category',
                    ],
                ],
            ],
        ]);
    }
}
