<?php

declare(strict_types=1);

namespace EslovCustomisation\AcfFields;

/**
 * Options page for named page-tree brand palettes (POC).
 */
class BrandPaletteFields
{
    public const PAGE_SLUG = 'eslov-brand-palettes';

    public const GROUP_KEY = 'group_eslov_brand_palettes';

    public const REPEATER_NAME = 'eslov_brand_palettes';

    public function __construct()
    {
        add_action('acf/init', [$this, 'register']);
    }

    /**
     * Register the options page and field group when ACF Pro is available.
     */
    public function register(): void
    {
        if (!function_exists('acf_add_options_page') || !function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_options_page([
            'page_title' => __('Sektionspaletter', 'eslov-customisation'),
            'menu_title' => __('Sektionspaletter', 'eslov-customisation'),
            'menu_slug' => self::PAGE_SLUG,
            'capability' => 'manage_options',
            'parent_slug' => 'themes.php',
            'redirect' => false,
            'update_button' => __('Spara paletter', 'eslov-customisation'),
            'updated_message' => __('Paletter sparade.', 'eslov-customisation'),
        ]);

        acf_add_local_field_group([
            'key' => self::GROUP_KEY,
            'title' => __('Sektionspaletter', 'eslov-customisation'),
            'fields' => [
                [
                    'key' => 'field_eslov_brand_palettes',
                    'label' => __('Paletter', 'eslov-customisation'),
                    'name' => self::REPEATER_NAME,
                    'type' => 'repeater',
                    'instructions' => __(
                        'Varje palett knyts till en rot-sida. Den sidan och alla undersidor ärver färgerna (sidhuvud, knappar och övrig krom följer med).',
                        'eslov-customisation'
                    ),
                    'required' => 0,
                    'layout' => 'block',
                    'button_label' => __('Lägg till palett', 'eslov-customisation'),
                    'collapsed' => 'field_eslov_brand_palette_name',
                    'sub_fields' => $this->repeaterSubFields(),
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => self::PAGE_SLUG,
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'active' => true,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function repeaterSubFields(): array
    {
        return [
            [
                'key' => 'field_eslov_brand_palette_name',
                'label' => __('Namn', 'eslov-customisation'),
                'name' => 'name',
                'type' => 'text',
                'instructions' => __('Endast för redaktörer, t.ex. Sommar.', 'eslov-customisation'),
                'required' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_eslov_brand_palette_root_page',
                'label' => __('Rot-sida', 'eslov-customisation'),
                'name' => 'root_page',
                'type' => 'post_object',
                'instructions' => __('Sektionen börjar på den här sidan i sidträdet.', 'eslov-customisation'),
                'required' => 1,
                'post_type' => ['page'],
                'return_format' => 'id',
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_eslov_brand_palette_color_primary',
                'label' => __('Primärfärg', 'eslov-customisation'),
                'name' => 'color_primary',
                'type' => 'color_picker',
                'required' => 1,
                'enable_opacity' => 0,
                'return_format' => 'string',
                'wrapper' => ['width' => '25'],
            ],
            [
                'key' => 'field_eslov_brand_palette_color_primary_contrast',
                'label' => __('Text på primärfärg', 'eslov-customisation'),
                'name' => 'color_primary_contrast',
                'type' => 'color_picker',
                'required' => 1,
                'enable_opacity' => 0,
                'return_format' => 'string',
                'wrapper' => ['width' => '25'],
            ],
            [
                'key' => 'field_eslov_brand_palette_color_secondary',
                'label' => __('Sekundärfärg', 'eslov-customisation'),
                'name' => 'color_secondary',
                'type' => 'color_picker',
                'required' => 0,
                'enable_opacity' => 0,
                'return_format' => 'string',
                'wrapper' => ['width' => '25'],
            ],
            [
                'key' => 'field_eslov_brand_palette_color_secondary_contrast',
                'label' => __('Text på sekundärfärg', 'eslov-customisation'),
                'name' => 'color_secondary_contrast',
                'type' => 'color_picker',
                'required' => 0,
                'enable_opacity' => 0,
                'return_format' => 'string',
                'wrapper' => ['width' => '25'],
            ],
        ];
    }
}
