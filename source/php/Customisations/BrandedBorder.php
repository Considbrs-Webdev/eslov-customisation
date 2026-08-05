<?php

namespace EslovCustomisation\Customisations;

/**
 * Polka-stripe site border (LTS ws-branded-border). Used on plus.eslov.se.
 *
 * Keeps legacy theme_mod keys so imported Customizer values apply without migration.
 */
class BrandedBorder
{
    private const SETTING_ENABLED = 'whitespace_branded_border_enabled';

    private const SETTING_COLOR1 = 'whitespace_branded_border_color1';

    private const SETTING_COLOR2 = 'whitespace_branded_border_color2';

    private const CUSTOMIZER_SECTION = 'whitespace_branded_border_section';

    private const CUSTOMIZER_PANEL = 'municipio_customizer_panel_design';

    public function __construct()
    {
        add_action('customize_register', [$this, 'registerCustomizerSettings']);
        add_filter('body_class', [$this, 'addBodyClasses']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueDynamicColors'], 101);
    }

    public function registerCustomizerSettings(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->add_section(self::CUSTOMIZER_SECTION, [
            'title'    => __('Ram runt webbplatsen', 'eslov-customisation'),
            'priority' => 200,
            'panel'    => self::CUSTOMIZER_PANEL,
        ]);

        $wp_customize->add_setting(self::SETTING_ENABLED, [
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        $wp_customize->add_control(self::SETTING_ENABLED, [
            'label'    => __('Aktivera ram runt webbplatsen', 'eslov-customisation'),
            'section'  => self::CUSTOMIZER_SECTION,
            'type'     => 'checkbox',
            'priority' => 10,
        ]);

        $wp_customize->add_setting(self::SETTING_COLOR1, [
            'default'           => '#FF70FE',
            'sanitize_callback' => 'sanitize_hex_color',
        ]);

        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, self::SETTING_COLOR1, [
            'label'    => __('Färg 1', 'eslov-customisation'),
            'section'  => self::CUSTOMIZER_SECTION,
            'priority' => 20,
        ]));

        $wp_customize->add_setting(self::SETTING_COLOR2, [
            'default'           => '#00FFFF',
            'sanitize_callback' => 'sanitize_hex_color',
        ]);

        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, self::SETTING_COLOR2, [
            'label'    => __('Färg 2', 'eslov-customisation'),
            'section'  => self::CUSTOMIZER_SECTION,
            'priority' => 30,
        ]));
    }

    /**
     * @param string[] $classes
     * @return string[]
     */
    public function addBodyClasses(array $classes): array
    {
        if (get_theme_mod(self::SETTING_ENABLED, false)) {
            $classes[] = 'branded-border';
            $classes[] = 'branded-border__custom';
        }

        return $classes;
    }

    public function enqueueDynamicColors(): void
    {
        if (!get_theme_mod(self::SETTING_ENABLED, false)) {
            return;
        }

        $color1 = get_theme_mod(self::SETTING_COLOR1, '#FF70FE');
        $color2 = get_theme_mod(self::SETTING_COLOR2, '#00FFFF');

        $css = sprintf(
            ':root { --eslov-branded-border-color1: %s; --eslov-branded-border-color2: %s; }',
            esc_attr((string) $color1),
            esc_attr((string) $color2),
        );

        $handle = wp_style_is('eslov-site-overrides', 'enqueued')
            ? 'eslov-site-overrides'
            : 'wp-block-library';

        wp_add_inline_style($handle, $css);
    }
}
