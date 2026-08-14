<?php

namespace EslovCustomisation\Navigation;

/**
 * Renders singular taxonomy pills via Municipio @tags (pill style).
 */
class TaglistRenderer
{
    /**
     * @param array<int, array{label: string, href?: string}> $tags
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
