<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\PostObject\PostObjectWithoutIcon;
use Modularity\Module\Posts\Posts;
use Municipio\PostObject\PostObjectInterface;

/**
 * Hide term-derived icons on mod-posts cards. No Municipio setting exists for this.
 */
class ModPostsHideTermIcons
{
    public function __construct()
    {
        add_filter('Modularity/Module/Posts/template', [$this, 'stripTermIconsFromPosts'], 1, 4);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function stripTermIconsFromPosts(
        string $template,
        Posts $module,
        array $data,
        array $fields
    ): string {
        if (!empty($module->data['posts']) && is_array($module->data['posts'])) {
            $module->data['posts'] = $this->wrapPosts($module->data['posts'], $fields);
        }

        if (!empty($module->data['stickyPosts']) && is_array($module->data['stickyPosts'])) {
            $module->data['stickyPosts'] = $this->wrapPosts($module->data['stickyPosts'], $fields);
        }

        return $template;
    }

    /**
     * @param array<int, mixed> $posts
     * @param array<string, mixed> $fields
     * @return array<int, mixed>
     */
    private function wrapPosts(array $posts, array $fields): array
    {
        $showCommentCount = in_array('comment_count', $fields['posts_fields'] ?? [], true);

        foreach ($posts as $index => $post) {
            if ($post instanceof PostObjectInterface) {
                $posts[$index] = new PostObjectWithoutIcon($post, $showCommentCount);
            }
        }

        return $posts;
    }
}
