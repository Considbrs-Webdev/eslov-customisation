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

        $replaced = preg_replace_callback(
            '/<p\b[^>]*>.*?<\/p>/is',
            function (array $matches): string {
                if (!str_contains($matches[0], self::PLACEHOLDER_PREFIX)) {
                    return $matches[0];
                }

                return $this->restorePlaceholdersInParagraph($matches[0]);
            },
            $content
        );

        $content = is_string($replaced) ? $replaced : $content;

        foreach ($this->placeholders as $token => $placeholder) {
            if (!str_contains($content, $token)) {
                continue;
            }

            $content = str_replace(
                $token,
                $this->expandShortcode($placeholder['id'], $placeholder['shortcode']),
                $content
            );
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

    /**
     * Lift placeholders out of a `wpautop` paragraph so block markup is not
     * injected inside `<p>`. Consecutive shortcodes often share one paragraph
     * with `<br />` between tokens.
     */
    private function restorePlaceholdersInParagraph(string $paragraph): string
    {
        if (!preg_match('/^(<p\b[^>]*>)(.*)(<\/p>)$/is', $paragraph, $matches)) {
            return $paragraph;
        }

        $tokenPattern = $this->placeholderTokenPattern();
        if ($tokenPattern === null) {
            return $paragraph;
        }

        $pieces = preg_split('/(' . $tokenPattern . ')/', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($pieces === false) {
            return $paragraph;
        }

        $output = [];
        foreach ($pieces as $piece) {
            if ($piece === '') {
                continue;
            }

            if (isset($this->placeholders[$piece])) {
                $placeholder = $this->placeholders[$piece];
                $output[] = $this->expandShortcode($placeholder['id'], $placeholder['shortcode']);
                continue;
            }

            $text = preg_replace('/^(?:<br\s*\/?>|\s)+|(?:<br\s*\/?>|\s)+$/i', '', $piece);
            if (!is_string($text) || $text === '') {
                continue;
            }

            $output[] = $matches[1] . $text . $matches[3];
        }

        return implode('', $output);
    }

    private function placeholderTokenPattern(): ?string
    {
        if ($this->placeholders === []) {
            return null;
        }

        $tokens = array_map(
            static fn (string $token): string => preg_quote($token, '/'),
            array_keys($this->placeholders)
        );

        return implode('|', $tokens);
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
