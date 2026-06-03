<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Navigation\ChildPageButtonsRenderer;

/**
 * Child page links below the article title (LTS municipio-extended button-navigation.php).
 */
class ChildPageLinksBelowTitle
{
    private const THEME_MOD_SECONDARY_NAV = 'secondary_navigation_position';

    private const POSITION_BELOW_TITLE = 'below_title';

    public function __construct()
    {
        add_action('article_content_before', [$this, 'render'], 20);
    }

    public function render(): void
    {
        if (!is_singular() || get_theme_mod(self::THEME_MOD_SECONDARY_NAV) !== self::POSITION_BELOW_TITLE) {
            return;
        }

        if (function_exists('get_field') && get_field('page_hide_secondary_menu')) {
            return;
        }

        $postId = get_the_ID();
        if (!$postId) {
            return;
        }

        $items = $this->buildChildPageItems($postId, (string) get_post_type($postId));
        ChildPageButtonsRenderer::render($items);
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function buildChildPageItems(int $parentId, string $postType): array
    {
        $childPosts = get_posts([
            'post_parent' => $parentId,
            'post_type' => $postType,
            'nopaging' => true,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_menu',
                    'value' => '1',
                    'compare' => '!=',
                ],
                [
                    'key' => 'hide_in_menu',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        $items = [];
        foreach ($childPosts as $child) {
            $title = function_exists('get_field')
                ? (get_field('custom_menu_title', $child->ID) ?: $child->post_title)
                : $child->post_title;

            $items[] = [
                'label' => $title,
                'href' => (string) get_permalink($child),
            ];
        }

        return $items;
    }
}
