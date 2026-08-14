<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Customizer\TaxonomyChecklistControl;
use EslovCustomisation\Support\SingularTaxonomySettings;

/**
 * Vanilla WP Customizer UI for singular taxonomy taglists (LTS municipio-extended mxui.php).
 *
 * Uses imported LTS theme_mod keys — no DB migration.
 */
class SingularTaxonomyCustomizer
{
    private const PANEL_ID = 'eslov_singular_taxonomies';

    public function __construct()
    {
        add_action('customize_register', [$this, 'registerSettings']);
        add_action('customize_controls_enqueue_scripts', [$this, 'enqueueControlScript']);
    }

    public function registerSettings(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->add_panel(self::PANEL_ID, [
            'title'       => __('Taxonomier på enskilda inlägg', 'eslov-customisation'),
            'description' => __(
                'Välj vilka taxonomier som visas som etiketter på enskilda inlägg och var de placeras.',
                'eslov-customisation',
            ),
            'priority'    => 165,
        ]);

        $priority = 10;

        foreach (SingularTaxonomySettings::getPostTypesWithPublicTaxonomies() as $postType) {
            $sectionId = self::PANEL_ID . '_' . $postType->name;
            $choices = SingularTaxonomySettings::getPublicTaxonomyChoices($postType->name);

            $wp_customize->add_section($sectionId, [
                'title'    => $postType->labels->singular_name,
                'panel'    => self::PANEL_ID,
                'priority' => $priority,
            ]);

            $taxonomiesKey = SingularTaxonomySettings::taxonomiesThemeModKey($postType->name);
            $placementKey = SingularTaxonomySettings::placementThemeModKey($postType->name);

            $wp_customize->add_setting($taxonomiesKey, [
                'default'           => false,
                'sanitize_callback'   => static fn ($value) => SingularTaxonomySettings::sanitizeTaxonomySelection(
                    $postType->name,
                    $value,
                ),
                'transport'           => 'refresh',
            ]);

            $wp_customize->add_control(new TaxonomyChecklistControl($wp_customize, $taxonomiesKey, [
                'label'       => __('Visa taxonomier', 'eslov-customisation'),
                'description' => __(
                    'Om inget är sparat visas alla publika taxonomier (samma som tidigare). Avmarkera alla och spara för att dölja etiketterna.',
                    'eslov-customisation',
                ),
                'section'     => $sectionId,
                'choices'     => $choices,
                'priority'    => 10,
            ]));

            $wp_customize->add_setting($placementKey, [
                'default'           => SingularTaxonomySettings::PLACEMENT_UNDER_HEADER,
                'sanitize_callback' => [SingularTaxonomySettings::class, 'sanitizePlacement'],
                'transport'         => 'refresh',
            ]);

            $wp_customize->add_control($placementKey, [
                'label'    => __('Placering', 'eslov-customisation'),
                'section'  => $sectionId,
                'type'     => 'select',
                'choices'  => [
                    SingularTaxonomySettings::PLACEMENT_UNDER_HEADER => __(
                        'Under rubrik (före innehåll)',
                        'eslov-customisation',
                    ),
                    SingularTaxonomySettings::PLACEMENT_AFTER_CONTENT => __(
                        'Efter innehåll',
                        'eslov-customisation',
                    ),
                ],
                'priority' => 20,
            ]);

            $priority += 10;
        }
    }

    public function enqueueControlScript(): void
    {
        $script = <<<'JS'
(function (api, $) {
    function bindChecklist(control) {
        if (!control || control.params.type !== 'eslov_taxonomy_checklist' || !control.container) {
            return;
        }

        if (control.container.data('eslovChecklistBound')) {
            return;
        }
        control.container.data('eslovChecklistBound', true);

        control.container.on('change', 'input[type="checkbox"]', function () {
            var values = [];
            control.container.find('input[type="checkbox"]:checked').each(function () {
                values.push(this.value);
            });
            control.setting.set(values);
        });
    }

    api.bind('ready', function () {
        api.control.each(bindChecklist);
        api.control.bind('add', bindChecklist);
    });
}(wp.customize, jQuery));
JS;

        wp_add_inline_script('customize-controls', $script);
    }
}
