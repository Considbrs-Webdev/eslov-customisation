<?php

namespace EslovCustomisation\Modules\Navigation;

class MenuItemNormalizer
{
    public function __construct(
        private readonly ImageAdapter $imageAdapter = new ImageAdapter(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(\WP_Post $menuItem, int $depth, string $menuSlug): array
    {
        $item = wp_setup_nav_menu_item($menuItem);
        $connectedPost = $this->getConnectedPost($item);
        $menuItemId = (int) $item->ID;

        $icon = null;
        if (function_exists('get_field')) {
            $icon = get_field('menu_item_icon', $menuItemId);
        }

        if (empty($icon) && $connectedPost) {
            $icon = get_field('page_navigation_icon', $connectedPost->ID);
        }

        $description = $item->description ?? '';
        if ($description === '' && $connectedPost) {
            $description = (string) (get_field('page_navigation_description', $connectedPost->ID) ?: '');
        }

        $color = null;
        if ($connectedPost) {
            $color = get_field('page_apperance_theme_color', $connectedPost->ID);
        }

        $childDepth = $depth - 1;
        $children = $childDepth > 0
            ? ItemResolver::getMenuItemsByMenu($menuSlug, $childDepth, $menuItemId)
            : [];

        return [
            'id' => $menuItemId,
            'href' => (string) ($item->url ?? ''),
            'title' => (string) ($item->title ?? ''),
            'image' => $this->imageAdapter->fromPost($connectedPost, 'tree'),
            'icon' => $icon,
            'color' => $color,
            'description' => $description,
            'children' => $children,
            'post' => $connectedPost,
        ];
    }

    private function getConnectedPost(object $item): ?\WP_Post
    {
        if (($item->type ?? '') !== 'post_type' || empty($item->object_id)) {
            return null;
        }

        $post = get_post((int) $item->object_id);

        return $post instanceof \WP_Post ? $post : null;
    }
}
