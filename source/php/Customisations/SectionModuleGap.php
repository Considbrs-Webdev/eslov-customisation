<?php

namespace EslovCustomisation\Customisations;

/**
 * Restore LTS "Remove spacing below" on section modules (gap to the next module).
 *
 * Reuses field_module_layout_remove_spacing_below so imported meta loads as-is.
 * Independent of Municipio Spacing Top/Bottom (inner segment padding).
 */
class SectionModuleGap
{
    private const FIELD_KEY = 'field_module_layout_remove_spacing_below';

    private const FIELD_NAME = 'module_layout_remove_spacing_below';

    private const WRAPPER_CLASS = '-u-margin-after--grid-gap';

    /**
     * @var string[]
     */
    private const POST_TYPES = [
        'mod-section-split',
        'mod-section-full',
        'mod-section-featured',
    ];

    public function __construct()
    {
        add_action('acf/init', [$this, 'registerFieldGroup'], 20);
        add_filter('Modularity/Display/BeforeModule::classes', [$this, 'appendGapClass'], 10, 4);
    }

    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_module_layout',
            'title' => _x('Layout', 'Module Field Group Label', 'eslov-customisation'),
            'fields' => [
                [
                    'key' => self::FIELD_KEY,
                    'label' => __('Remove spacing below', 'eslov-customisation'),
                    'name' => self::FIELD_NAME,
                    'type' => 'true_false',
                    'instructions' => __(
                        'Check this to remove the spacing below this section.',
                        'eslov-customisation'
                    ),
                    'ui' => 1,
                ],
            ],
            'location' => array_map(
                static fn (string $postType): array => [[
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => $postType,
                ]],
                self::POST_TYPES,
            ),
        ]);
    }

    /**
     * @param string[] $classes
     * @param array<string, mixed> $args
     * @return string[]
     */
    public function appendGapClass(array $classes, array $args, string $moduleType, int $moduleId): array
    {
        if (!in_array($moduleType, self::POST_TYPES, true)) {
            return $classes;
        }

        if (!function_exists('get_field') || !get_field(self::FIELD_NAME, $moduleId)) {
            return $classes;
        }

        $classes[] = self::WRAPPER_CLASS;

        return $classes;
    }
}
