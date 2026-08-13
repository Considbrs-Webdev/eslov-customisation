<?php

namespace EslovCustomisation\Customisations;

/**
 * Render nested `[modularity]` shortcodes in Text modules after `wpautop`.
 *
 * Municipio 7 strips `[modularity]` in `Modularity/Display/SanitizeContent`
 * before `the_content`. Expanding shortcodes there lets `wpautop` mangle the
 * nested module HTML. Placeholders survive the sanitizer and `wpautop`;
 * expansion runs afterwards (same order as LTS: wpautop, then do_shortcode).
 */
class NestedModularityShortcodes
{
    private const PLACEHOLDER_PREFIX = 'NESTEDMODULARITY';

    /**
     * @var array<string, array{id: int, shortcode: string}>
     */
    private array $placeholders = [];

    /**
     * @var array<int, bool>
     */
    private array $rendering = [];

    public function __construct()
    {
        add_filter('Modularity/Display/SanitizeContent', [$this, 'deferShortcodes'], 9);
        add_filter('the_content', [$this, 'restoreShortcodes'], 12);
        add_filter('Modularity/Display/mod-text/viewData', [$this, 'restoreViewData']);
    }

    /**
     * Replace nested `[modularity]` shortcodes with placeholders.
     *
     * @param mixed $content
     */
    public function deferShortcodes($content): string
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

        $deferredContent = preg_replace_callback(
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

                $token = $this->createPlaceholder();
                $this->placeholders[$token] = [
                    'id' => $moduleId,
                    'shortcode' => $matches[0],
                ];

                return $token;
            },
            $content
        );

        return is_string($deferredContent) ? $deferredContent : $content;
    }

    /**
     * Expand placeholders after `wpautop` has run.
     *
     * @param mixed $content
     */
    public function restoreShortcodes($content): string
    {
        if (!is_string($content) || $this->placeholders === []) {
            return is_string($content) ? $content : '';
        }

        if (!str_contains($content, self::PLACEHOLDER_PREFIX)) {
            return $content;
        }

        foreach ($this->placeholders as $token => $placeholder) {
            if (!str_contains($content, $token)) {
                continue;
            }

            $markup = $this->expandShortcode($placeholder['id'], $placeholder['shortcode']);
            $content = $this->replacePlaceholder($content, $token, $markup);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function restoreViewData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach (['postContent', 'post_content'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                $data[$key] = $this->restoreShortcodes($data[$key]);
            }
        }

        return $data;
    }

    private function expandShortcode(int $moduleId, string $shortcode): string
    {
        if ($moduleId <= 0 || isset($this->rendering[$moduleId])) {
            return '';
        }

        $this->rendering[$moduleId] = true;
        $markup = do_shortcode($shortcode);
        unset($this->rendering[$moduleId]);

        return is_string($markup) ? $markup : '';
    }

    private function replacePlaceholder(string $content, string $token, string $markup): string
    {
        $wrapped = [
            '<p>' . $token . '</p>',
            '<p>' . $token . '<br /></p>',
            '<p>' . $token . '<br/></p>',
            '<p>' . $token . '<br></p>',
        ];

        $content = str_replace($wrapped, $markup, $content);

        return str_replace($token, $markup, $content);
    }

    private function createPlaceholder(): string
    {
        return self::PLACEHOLDER_PREFIX . strtoupper(bin2hex(random_bytes(8)));
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
