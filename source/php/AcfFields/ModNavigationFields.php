<?php

namespace EslovCustomisation\AcfFields;

class ModNavigationFields
{
    public function __construct()
    {
        add_action('acf/init', [$this, 'register'], 5);
    }

    public function register(): void
    {
        if (!function_exists('acf_add_local_field_group') || !function_exists('acf_get_field_group')) {
            return;
        }

        if (acf_get_field_group('group_mod_navigation')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_mod_navigation',
            'title' => __('Navigation module', 'eslov-customisation'),
            'fields' => array_values($this->fields()),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mod-navigation',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(): array
    {
        return apply_filters('mx_mod_navigation_fields', [
            'mod_navigation_format' => [
                'key' => 'field_mod_navigation_format',
                'label' => __('Format', 'eslov-customisation'),
                'name' => 'mod_navigation_format',
                'type' => 'select',
                'required' => 1,
                'return_format' => 'value',
                'choices' => [
                    'list' => __('List', 'eslov-customisation'),
                    'grid' => __('Grid', 'eslov-customisation'),
                    'bar' => __('Bar', 'eslov-customisation'),
                    'tree' => __('Tree', 'eslov-customisation'),
                    'cards' => __('Cards', 'eslov-customisation'),
                    'buttons' => __('Buttons', 'eslov-customisation'),
                    'inline' => __('Inline', 'eslov-customisation'),
                ],
            ],
            'mod_navigation_source' => [
                'key' => 'field_mod_navigation_source',
                'label' => __('Source', 'eslov-customisation'),
                'name' => 'mod_navigation_source',
                'type' => 'select',
                'return_format' => 'value',
                'choices' => [
                    'children' => __('Child pages', 'eslov-customisation'),
                    'siblings' => __('Sibling pages', 'eslov-customisation'),
                    'menu' => __('Menu', 'eslov-customisation'),
                    'manual' => __('Manually selected', 'eslov-customisation'),
                ],
            ],
            'mod_navigation_menu' => [
                'key' => 'field_mod_navigation_menu',
                'label' => __('Menu', 'eslov-customisation'),
                'name' => 'mod_navigation_menu',
                'type' => 'select',
                'return_format' => 'value',
                'choices' => $this->menuChoices(),
                'allow_null' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_mod_navigation_source',
                            'operator' => '==',
                            'value' => 'menu',
                        ],
                    ],
                ],
            ],
            'mod_navigation_items' => [
                'key' => 'field_mod_navigation_items',
                'label' => __('Items', 'eslov-customisation'),
                'name' => 'mod_navigation_items',
                'type' => 'repeater',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_mod_navigation_source',
                            'operator' => '==',
                            'value' => 'manual',
                        ],
                    ],
                ],
                'sub_fields' => [
                    [
                        'key' => 'field_mod_navigation_item_link',
                        'label' => __('Link', 'eslov-customisation'),
                        'name' => 'link',
                        'type' => 'link',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_mod_navigation_color',
                        'label' => __('Color', 'eslov-customisation'),
                        'name' => 'color',
                        'type' => 'color_picker',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_mod_navigation_format',
                                    'operator' => '==',
                                    'value' => 'grid',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_mod_navigation_button_variant',
                        'label' => __('Variant', 'eslov-customisation'),
                        'name' => 'button_variant',
                        'type' => 'select',
                        'choices' => [
                            'primary' => __('Primary', 'eslov-customisation'),
                            'secondary' => __('Secondary', 'eslov-customisation'),
                            'default' => __('Default', 'eslov-customisation'),
                        ],
                        'default_value' => 'default',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_mod_navigation_format',
                                    'operator' => '==',
                                    'value' => 'buttons',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_mod_navigation_item_icon',
                        'label' => __('Icon', 'eslov-customisation'),
                        'name' => 'icon',
                        'type' => 'text',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_mod_navigation_format',
                                    'operator' => '!=',
                                    'value' => 'tree',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'mod_navigation_show_if_empty' => [
                'key' => 'field_mod_navigation_show_if_empty',
                'label' => __('Display this module even when there are no items', 'eslov-customisation'),
                'name' => 'mod_navigation_show_if_empty',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
            ],
            'mod_navigation_empty_message' => [
                'key' => 'field_mod_navigation_empty_message',
                'label' => __('Message to display when there are no items', 'eslov-customisation'),
                'name' => 'mod_navigation_empty_message',
                'type' => 'wysiwyg',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_mod_navigation_show_if_empty',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function menuChoices(): array
    {
        $choices = [];
        $menus = wp_get_nav_menus();

        if (!is_array($menus)) {
            return $choices;
        }

        foreach ($menus as $menu) {
            $choices[$menu->slug] = $menu->name;
        }

        return $choices;
    }
}
