<?php

namespace EslovCustomisation\Customisations;

class SiteScripts
{
    private const HANDLE = 'eslov-site';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueSiteScripts'], 100);
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);
    }

    public function enqueueSiteScripts(): void
    {
        $manifestPath = ESLOV_CUSTOMISATION_PATH . 'assets/dist/manifest.json';
        if (!is_readable($manifestPath)) {
            return;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest) || !isset($manifest['js/site.js'])) {
            return;
        }

        $relativePath = 'assets/dist/' . $manifest['js/site.js'];
        $url = plugins_url($relativePath, ESLOV_CUSTOMISATION_PATH . 'eslov-customisation.php');

        wp_enqueue_script(
            self::HANDLE,
            $url,
            ['js-styleguidejs'],
            ESLOV_CUSTOMISATION_VERSION,
            true,
        );
    }

    /**
     * Vite emits ES modules; WordPress must mark the tag accordingly.
     *
     * @param string $tag
     * @param string $handle
     */
    public function addModuleType(string $tag, string $handle): string
    {
        if ($handle !== self::HANDLE || str_contains($tag, 'type=')) {
            return $tag;
        }

        return (string) preg_replace('/<script\b/', '<script type="module"', $tag, 1);
    }
}
