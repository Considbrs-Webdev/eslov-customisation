<?php

namespace EslovCustomisation\Navigation;

/**
 * Renders singular taxonomy chips (LTS mxui.taglist appearance).
 */
class TaglistRenderer
{
    /**
     * @param array<int, array{label: string, href?: string, color?: string}> $tags
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
