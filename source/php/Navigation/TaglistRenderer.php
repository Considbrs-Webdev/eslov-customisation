<?php

namespace EslovCustomisation\Navigation;

/**
 * Renders taxonomy pills using component-library c-tags__tag markup (replaces LTS mxui.taglist).
 */
class TaglistRenderer
{
    /**
     * @param array<int, array{label: string, href?: string, color?: string|null}> $tags
     */
    public static function render(array $tags): void
    {
        if ($tags === [] || !function_exists('render_blade_view')) {
            return;
        }

        echo render_blade_view('partials.article-taglist', [
            'tags' => $tags,
        ]);
    }
}
