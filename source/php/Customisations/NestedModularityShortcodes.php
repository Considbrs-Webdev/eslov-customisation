<?php

namespace EslovCustomisation\Customisations;

/**
 * Render legacy nested Modularity shortcodes before Municipio strips them.
 */
class NestedModularityShortcodes
{
    /**
     * @var array<int, bool>
     */
    private array $rendering = [];

    public function __construct()
    {
        add_filter('Modularity/Display/SanitizeContent', [$this, 'renderShortcodes'], 9);
    }

    public function renderShortcodes($content): string
    {
        if (!is_string($content) || stripos($content, '[modularity') === false) {
            return is_string($content) ? $content : '';
        }

        if ($this->isAdminScreen()) {
            return $content;
        }

        if (!shortcode_exists('modularity')) {
            return $content;
        }

        $pattern = get_shortcode_regex(['modularity']);

        $renderedContent = preg_replace_callback(
            '/' . $pattern . '/s',
            function (array $matches): string {
                if (($matches[1] ?? '') === '[' && ($matches[6] ?? '') === ']') {
                    return substr($matches[0], 1, -1);
                }

                $attributes = shortcode_parse_atts($matches[3] ?? '');
                $attributes = is_array($attributes) ? $attributes : [];
                $moduleId = isset($attributes['id']) ? (int) $attributes['id'] : 0;

                if ($moduleId <= 0 || isset($this->rendering[$moduleId])) {
                    return '';
                }

                $this->rendering[$moduleId] = true;
                $markup = do_shortcode($matches[0]);
                unset($this->rendering[$moduleId]);

                return is_string($markup) ? $markup : '';
            },
            $content
        );

        return is_string($renderedContent) ? $renderedContent : $content;
    }

    private function isAdminScreen(): bool
    {
        if (!is_admin()) {
            return false;
        }

        if (wp_doing_ajax()) {
            return false;
        }

        return !defined('REST_REQUEST') || !REST_REQUEST;
    }
}
