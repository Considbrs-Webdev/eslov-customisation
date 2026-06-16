<?php

namespace EslovCustomisation\AcfFields;

/**
 * Eslöv multi-taxonomy filter repeater for mod-posts (LTS municipio-extended parity).
 */
class ModPostsFilteringFields
{
    private const DATA_FILTERING_GROUP = 'group_571e045dd555d';

    private const TAXONOMY_FILTER_TOGGLE = 'field_571e046536f0f';

    private const REPEATER_FIELD_KEY = 'field_mod_posts_filtering';

    public function __construct()
    {
        add_action('init', [$this, 'register'], 11);
        add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);

        if (did_action('init')) {
            $this->register();
        }
    }

    public function register(): void
    {
        $this->registerRepeater();
        $this->removeStandardFields();
    }

    public function registerRepeater(): void
    {
        if (!function_exists('acf_add_local_field') || !function_exists('acf_get_field')) {
            return;
        }

        if (acf_get_field(self::REPEATER_FIELD_KEY)) {
            return;
        }

        if (!acf_get_field_group(self::DATA_FILTERING_GROUP)) {
            return;
        }

        acf_add_local_field([
            'parent' => self::DATA_FILTERING_GROUP,
            'key' => self::REPEATER_FIELD_KEY,
            'label' => __('Taxonomifilter', 'eslov-customisation'),
            'name' => 'mod_posts_filtering',
            'type' => 'repeater',
            'instructions' => __(
                'Endast inlägg som uppfyller alla dessa villkor kommer visas i modulen.',
                'eslov-customisation'
            ),
            'required' => 0,
            'conditional_logic' => [
                [
                    [
                        'field' => self::TAXONOMY_FILTER_TOGGLE,
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
            ],
            'wrapper' => [
                'width' => '',
                'class' => 'acf-field-mod-posts-filtering',
                'id' => '',
            ],
            'collapsed' => '',
            'min' => 1,
            'max' => 0,
            'layout' => 'block',
            'button_label' => __('Lägg till filter', 'eslov-customisation'),
            'sub_fields' => $this->repeaterSubFields(),
        ]);
    }

    public function removeStandardFields(): void
    {
        if (!function_exists('acf_remove_local_field')) {
            return;
        }

        foreach (['field_571e048136f10', 'field_609a6c2fae66e', 'field_571e049636f11'] as $fieldKey) {
            acf_remove_local_field($fieldKey);
        }
    }

    public function enqueueAdminStyles(): void
    {
        $css = '
            #acf-group_571e045dd555d > .acf-fields {
                display: flex;
                flex-wrap: wrap;
                align-items: stretch;
            }
            #acf-group_571e045dd555d > .acf-fields > .acf-field {
                width: 100%;
            }
            .acf-field-571e046536f0f {
                order: -2;
            }
            .acf-field-mod-posts-filtering {
                order: -1;
            }
        ';

        wp_register_style('eslov-mod-posts-filtering', false);
        wp_enqueue_style('eslov-mod-posts-filtering');
        wp_add_inline_style('eslov-mod-posts-filtering', $css);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function repeaterSubFields(): array
    {
        $taxChoices = $this->taxonomyChoices();
        $subFields = [
            [
                'key' => 'field_mod_posts_filtering_taxonomy',
                'label' => __('Taxonomi', 'eslov-customisation'),
                'name' => 'taxonomy',
                'type' => 'select',
                'required' => 1,
                'choices' => $taxChoices,
                'default_value' => false,
                'allow_null' => 0,
                'return_format' => 'value',
            ],
            [
                'key' => 'field_mod_posts_filtering_operator',
                'label' => __('Operator', 'eslov-customisation'),
                'name' => 'operator',
                'type' => 'select',
                'required' => 0,
                'choices' => [
                    'IN' => __('Är lika med', 'eslov-customisation'),
                    'NOT IN' => __('Är inte lika med', 'eslov-customisation'),
                ],
                'default_value' => 'IN',
                'allow_null' => 0,
                'return_format' => 'value',
            ],
        ];

        foreach (array_keys($taxChoices) as $taxonomy) {
            $subFields[] = [
                'key' => "field_mod_posts_filtering_term_{$taxonomy}",
                'label' => __('Term', 'eslov-customisation'),
                'name' => "term_{$taxonomy}",
                'type' => 'taxonomy',
                'required' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_mod_posts_filtering_taxonomy',
                            'operator' => '==',
                            'value' => $taxonomy,
                        ],
                    ],
                ],
                'taxonomy' => $taxonomy,
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'id',
                'multiple' => 0,
            ];
        }

        return $subFields;
    }

    /**
     * @return array<string, string>
     */
    private function taxonomyChoices(): array
    {
        $postTypes = get_post_types([], 'objects');
        $taxonomies = get_taxonomies(
            [
                'public' => true,
                'show_ui' => true,
            ],
            'objects'
        );

        $choices = [];

        foreach ($taxonomies as $taxonomyName => $taxonomy) {
            $label = $taxonomy->label;
            $objectTypes = $taxonomy->object_type ?? [];

            if ($objectTypes !== []) {
                $typeLabels = [];
                foreach ($objectTypes as $postType) {
                    if (isset($postTypes[$postType])) {
                        $typeLabels[] = $postTypes[$postType]->label;
                    }
                }

                if ($typeLabels !== []) {
                    $label .= ' (' . implode(', ', $typeLabels) . ')';
                }
            }

            $choices[$taxonomyName] = $label;
        }

        return $choices;
    }
}
