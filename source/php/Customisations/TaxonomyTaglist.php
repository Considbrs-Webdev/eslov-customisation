<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Navigation\TaglistRenderer;

/**
 * Taxonomy term pills on singular content (LTS municipio-extended autoload/post.php).
 */
class TaxonomyTaglist
{
    private const PLACEMENT_UNDER_HEADER = 'under_header';

    private const PLACEMENT_AFTER_CONTENT = 'after_content';

    public function __construct()
    {
        add_action('wp', [$this, 'registerPlacementHook'], 10);
    }

    public function registerPlacementHook(): void
    {
        if (!is_singular()) {
            return;
        }

        $postId = get_the_ID();
        $postType = get_post_type($postId);
        if (!$postId || !$postType) {
            return;
        }

        $placement = $this->getTaxonomyPlacement((string) $postType);
        $hook = $placement === self::PLACEMENT_AFTER_CONTENT
            ? 'article_content_after'
            : 'article_content_before';

        add_action($hook, function () use ($postId, $postType): void {
            $this->renderForPost($postId, $postType);
        }, 25);
    }

    public function renderForPost(int $postId, string $postType): void
    {
        $tags = $this->buildTaxonomyTags($postId, $postType);
        TaglistRenderer::render($tags);
    }

    /**
     * @return array<int, array{label: string, href?: string, color?: string|null}>
     */
    private function buildTaxonomyTags(int $postId, string $postType): array
    {
        $selected = $this->getSelectedTaxonomies($postType);
        if ($selected === []) {
            return [];
        }

        $tags = [];
        foreach (get_object_taxonomies($postType, 'objects') as $taxonomy) {
            if (!in_array($taxonomy->name, $selected, true)) {
                continue;
            }

            $terms = wp_get_post_terms($postId, $taxonomy->name);
            if (is_wp_error($terms) || $terms === []) {
                continue;
            }

            foreach ($terms as $term) {
                $tags[] = [
                    'label' => $term->name,
                    'href' => $this->getTermHref($term),
                    'color' => $this->getTermColor($term),
                ];
            }
        }

        return $tags;
    }

    private function getTaxonomyPlacement(string $postType): string
    {
        $placement = get_theme_mod(
            'municipio_customizer_panel_content_types_' . $postType . '_taxonomy_placement'
        );

        return is_string($placement) && $placement !== ''
            ? $placement
            : self::PLACEMENT_UNDER_HEADER;
    }

    /**
     * @return string[]
     */
    private function getSelectedTaxonomies(string $postType): array
    {
        $selected = get_theme_mod(
            'municipio_customizer_panel_content_types_' . $postType . '_taxonomies'
        );

        if (is_array($selected) && $selected !== []) {
            return array_values(array_filter($selected, 'is_string'));
        }

        return [];
    }

    private function getTermHref(\WP_Term $term): ?string
    {
        $redirect = get_term_meta($term->term_id, 'redirect_to', true);
        if (is_array($redirect) && !empty($redirect['url'])) {
            return (string) $redirect['url'];
        }

        return null;
    }

    private function getTermColor(\WP_Term $term): ?string
    {
        if (function_exists('get_field')) {
            $color = get_field('colour', $term->taxonomy . '_' . $term->term_id);
            if (is_string($color) && $color !== '') {
                return $color;
            }
        }

        $meta = get_term_meta($term->term_id, 'colour', true);

        return is_string($meta) && $meta !== '' ? $meta : null;
    }
}
