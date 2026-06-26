<?php

namespace EslovCustomisation\Modules\Navigation;

class ItemResolver
{
    private string $imageContext = 'card';

    public function __construct(
        private readonly int|string|null $moduleId,
        private readonly string $moduleSlug,
        private readonly ImageAdapter $imageAdapter = new ImageAdapter(),
        private readonly MenuItemNormalizer $menuItemNormalizer = new MenuItemNormalizer(),
    ) {
    }

    /**
     * @param callable(string): mixed $getField
     * @return array<int, array<string, mixed>>
     */
    public function resolve(callable $getField): array
    {
        $format = (string) ($getField('mod_navigation_format') ?: '');
        $this->imageContext = $format === 'tree' ? 'tree' : 'card';
        $depth = $getField('mod_navigation_depth');
        $depth = is_numeric($depth) ? (int) $depth : ($format === 'tree' ? 2 : 1);

        $source = (string) ($getField('mod_navigation_source') ?: '');

        return match ($source) {
            'children' => $this->getChildren($depth),
            'siblings' => $this->getSiblings(),
            'menu' => $this->getMenuItems($depth, $getField),
            default => $this->getManualItems($getField),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMenuItemsByMenu(string $menuSlug, int $depth = 1, int $postParent = 0): array
    {
        if ($depth <= 0 || $menuSlug === '') {
            return [];
        }

        $menuItems = wp_get_nav_menu_items($menuSlug);
        if (!is_array($menuItems)) {
            return [];
        }

        $menuItems = array_filter($menuItems, static function ($item) use ($postParent) {
            return (int) $item->menu_item_parent === $postParent;
        });

        if ($menuItems === []) {
            return [];
        }

        $normalizer = new MenuItemNormalizer();

        return array_values(array_map(
            static function (\WP_Post $item) use ($menuSlug, $depth, $normalizer) {
                return $normalizer->normalize($item, $depth, $menuSlug);
            },
            $menuItems,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getChildren(int $depth = 1, ?int $postId = null): array
    {
        if ($depth <= 0) {
            return [];
        }

        $post = get_post($postId);
        if (!$post instanceof \WP_Post) {
            return [];
        }

        $useNp = apply_filters(
            'mx_mod_navigation_use_nested_pages',
            false,
            $post,
            'children',
            $this->moduleSlug,
            $this->moduleId,
        );

        if ($useNp) {
            $npItems = $this->getNestedPagesMenuItems($post, $depth);
            if ($npItems !== []) {
                return $npItems;
            }
        }

        $childPosts = get_posts([
            'post_parent' => $post->ID,
            'post_type' => $post->post_type,
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

        return array_map(function (\WP_Post $child) use ($depth) {
            return $this->mapPostToItem($child, $this->getChildren($depth - 1, $child->ID));
        }, $childPosts);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSiblings(): array
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return [];
        }

        $useNp = apply_filters(
            'mx_mod_navigation_use_nested_pages',
            false,
            $post,
            'siblings',
            $this->moduleSlug,
            $this->moduleId,
        );

        if ($useNp) {
            $npItems = $this->getNestedPagesMenuItems($post, 1);
            if ($npItems !== []) {
                return $npItems;
            }
        }

        $siblingPosts = get_posts([
            'post_parent' => $post->post_parent,
            'post_type' => $post->post_type,
            'post__not_in' => [$post->ID],
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

        return array_map(fn (\WP_Post $sibling) => $this->mapPostToItem($sibling), $siblingPosts);
    }

    /**
     * @param callable(string): mixed $getField
     * @return array<int, array<string, mixed>>
     */
    private function getMenuItems(int $depth, callable $getField): array
    {
        if ($depth <= 0) {
            return [];
        }

        $menuSlug = (string) ($getField('mod_navigation_menu') ?: '');
        if ($menuSlug === '') {
            return [];
        }

        return self::getMenuItemsByMenu($menuSlug, $depth);
    }

    /**
     * @param callable(string): mixed $getField
     * @return array<int, array<string, mixed>>
     */
    private function getManualItems(callable $getField): array
    {
        $items = $getField('mod_navigation_items');
        if (!is_array($items) || $items === []) {
            return [];
        }

        return array_map(function (array $item) {
            $url = $item['link']['url'] ?? '';
            $postId = $url ? (int) url_to_postid($url) : 0;
            $post = $postId ? get_post($postId) : null;

            $title = $item['link']['title'] ?? '';
            if ($title === '' && $post) {
                $title = (string) (get_field('custom_menu_title', $post->ID) ?: $post->post_title);
            }

            return [
                'post' => $post,
                'title' => $title,
                'href' => (string) $url,
                'image' => $this->imageAdapter->fromPost($post, $this->imageContext),
                'icon' => $item['icon'] ?? ($post ? get_field('page_navigation_icon', $post->ID) : null),
                'description' => $post ? get_field('page_navigation_description', $post->ID) : null,
                'color' => $item['color'] ?? ($post ? get_field('page_apperance_theme_color', $post->ID) : null),
                'buttonVariant' => $item['button_variant'] ?? 'default',
                'children' => [],
            ];
        }, $items);
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private function mapPostToItem(\WP_Post $post, array $children = []): array
    {
        return [
            'id' => $post->ID,
            'post' => $post,
            'title' => (string) (get_field('custom_menu_title', $post->ID) ?: $post->post_title),
            'href' => (string) get_permalink($post->ID),
            'image' => $this->imageAdapter->fromPost($post, $this->imageContext),
            'icon' => get_field('page_navigation_icon', $post->ID),
            'description' => get_field('page_navigation_description', $post->ID),
            'color' => get_field('page_apperance_theme_color', $post->ID),
            'children' => $children,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNestedPagesMenuItems(\WP_Post $post, int $depth): array
    {
        $npMenu = get_option('nestedpages_menu');
        if (!$npMenu) {
            return [];
        }

        $menuItems = wp_get_associated_nav_menu_items($post->ID);
        $menuItems = array_filter($menuItems, static function ($menuItemId) use ($npMenu) {
            return has_term($npMenu, 'nav_menu', $menuItemId);
        });

        foreach ($menuItems as $menuItemId) {
            $items = self::getMenuItemsByMenu((string) $npMenu, $depth, (int) $menuItemId);
            if ($items !== []) {
                return $items;
            }
        }

        return [];
    }
}
