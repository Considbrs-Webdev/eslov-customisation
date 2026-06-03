<?php

namespace EslovCustomisation\Navigation;

/**
 * Child page link row via @button (LTS mxui.navigation.buttons).
 */
class ChildPageButtonsRenderer
{
    /**
     * @param array<int, array{label: string, href: string}> $items
     */
    public static function render(array $items): void
    {
        if ($items === [] || !function_exists('render_blade_view')) {
            return;
        }

        echo render_blade_view('partials.article-child-buttons', [
            'items' => $items,
        ]);
    }
}
