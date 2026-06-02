<?php

namespace EslovCustomisation\Customisations;

/**
 * Global plugin setup and ongoing site preferences.
 *
 * LTS mu-plugin filters from eslov-se (e.g. settings.php) can be added here
 * as runtime shims when still needed after data migration.
 */
class Config
{
    public function __construct()
    {
        add_action('init', [$this, 'loadTextdomain']);
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'eslov-customisation',
            false,
            dirname(dirname(dirname(__DIR__))) . '/languages'
        );
    }
}
