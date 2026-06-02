<?php

namespace EslovCustomisation\Navigation;

/**
 * Renders linked tag pills via Municipio @tags (replaces LTS mxui.taglist + tailwind wrapper).
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
