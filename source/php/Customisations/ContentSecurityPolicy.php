<?php

namespace EslovCustomisation\Customisations;

/**
 * Shared CSP sources for Mediaflow and Font Awesome on every site using this plugin.
 */
class ContentSecurityPolicy
{
    private const SOURCES = [
        'img-src' => ['mfstatic.com'],
        'media-src' => ['mfstatic.com'],
        'script-src' => ['mfstatic.com'],
        'style-src' => ['mfstatic.com'],
        'object-src' => ['mfstatic.com'],
        'connect-src' => ['mfstatic.com', 'https://*.fontawesome.com', 'https://*.mediaflow.com'],
    ];

    public function __construct()
    {
        // Run after wpmu-security has added the site's configured sources.
        add_filter('WpSecurity/Csp', [$this, 'addSources'], 20);
    }

    public function addSources(array $policies): array
    {
        foreach (self::SOURCES as $directive => $sources) {
            // The resolvers use 'none' when no source was found in the markup.
            // Remove it when adding allowed sources to this directive.
            $existing = array_diff($policies[$directive] ?? [], ["'none'"]);
            $policies[$directive] = array_values(array_unique(array_merge($existing, $sources)));
        }

        return $policies;
    }
}
