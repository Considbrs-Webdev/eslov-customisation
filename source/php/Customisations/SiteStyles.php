<?php

namespace EslovCustomisation\Customisations;

class SiteStyles
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueSiteStyles'], 100);
    }

    public function enqueueSiteStyles(): void
    {
        $manifestPath = ESLOV_CUSTOMISATION_PATH . 'assets/dist/manifest.json';
        if (!is_readable($manifestPath)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($manifest) || !isset($manifest['css/site-overrides.css'])) {
            return;
        }

        $relativePath = 'assets/dist/' . $manifest['css/site-overrides.css'];
        $url = plugins_url($relativePath, ESLOV_CUSTOMISATION_PATH . 'eslov-customisation.php');

        wp_enqueue_style(
            'eslov-site-overrides',
            $url,
            ['styleguide-design-builder-output', 'css-municipiocss'],
            ESLOV_CUSTOMISATION_VERSION,
        );
    }
}
