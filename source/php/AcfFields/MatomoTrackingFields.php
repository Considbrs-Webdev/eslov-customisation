<?php

declare(strict_types=1);

namespace EslovCustomisation\AcfFields;

use EslovCustomisation\Support\MatomoSettings;

/**
 * Options page (Inställningar → Matomo) for the Matomo tracking snippet.
 */
class MatomoTrackingFields
{
    public const PAGE_SLUG = 'eslov-matomo';

    public const GROUP_KEY = 'group_eslov_matomo';

    public function __construct()
    {
        add_action('acf/init', [$this, 'register']);

        foreach (MatomoSettings::KEYS as $key) {
            add_filter("acf/load_value/name=eslov_matomo_{$key}", [$this, 'prefillFromLegacy'], 10, 3);
        }
    }

    public function register(): void
    {
        if (!function_exists('acf_add_options_sub_page') || !function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_options_sub_page([
            'page_title' => __('Matomo', 'eslov-customisation'),
            'menu_title' => __('Matomo', 'eslov-customisation'),
            'menu_slug' => self::PAGE_SLUG,
            'parent_slug' => 'options-general.php',
            'capability' => 'manage_options',
            'autoload' => true,
        ]);

        acf_add_local_field_group([
            'key' => self::GROUP_KEY,
            'title' => __('Matomo', 'eslov-customisation'),
            'fields' => [
                [
                    'key' => 'field_eslov_matomo_url',
                    'label' => __('URL', 'eslov-customisation'),
                    'name' => 'eslov_matomo_url',
                    'type' => 'text',
                    'instructions' => __('Adress till Matomo, t.ex. https://analys.eslov.se/', 'eslov-customisation'),
                ],
                [
                    'key' => 'field_eslov_matomo_container_id',
                    'label' => __('Container ID', 'eslov-customisation'),
                    'name' => 'eslov_matomo_container_id',
                    'type' => 'text',
                    'instructions' => __('Fyll i för Matomo Tag Manager. Lämna tomt för klassisk Matomo-spårning.', 'eslov-customisation'),
                ],
                [
                    'key' => 'field_eslov_matomo_site_id',
                    'label' => __('Site ID', 'eslov-customisation'),
                    'name' => 'eslov_matomo_site_id',
                    'type' => 'text',
                    'instructions' => __('Används vid klassisk Matomo-spårning (utan Container ID).', 'eslov-customisation'),
                ],
                [
                    'key' => 'field_eslov_matomo_require_consent',
                    'label' => __('Kräv samtycke innan laddning', 'eslov-customisation'),
                    'name' => 'eslov_matomo_require_consent',
                    'type' => 'true_false',
                    'instructions' => __(
                        'Matomo laddas först när besökaren godkänt kategorin "Analys" i Pressidium Cookie Consent. Kräver att "Page Scripts" är påslaget i Pressidium-inställningarna.',
                        'eslov-customisation'
                    ),
                    'message' => __('Vänta på samtycke (Analys)', 'eslov-customisation'),
                    'default_value' => 0,
                    'ui' => 1,
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
     * @param mixed                $value
     * @param int|string           $postId
     * @param array<string, mixed> $field
     *
     * @return mixed
     */
    public function prefillFromLegacy($value, $postId, array $field)
    {
        if (!empty($value) || $postId !== 'options') {
            return $value;
        }

        $key = preg_replace('/^eslov_matomo_/', '', (string) ($field['name'] ?? ''));

        return in_array($key, MatomoSettings::KEYS, true) ? MatomoSettings::legacy($key) : $value;
    }
}
