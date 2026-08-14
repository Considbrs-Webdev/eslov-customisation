<?php

namespace EslovCustomisation\Customisations;

use EslovCustomisation\Navigation\TaglistRenderer;
use EslovCustomisation\Support\SingularTaxonomySettings;

/**
 * Taxonomy term pills on singular content (LTS municipio-extended autoload/post.php).
 */
class TaxonomyTaglist
{
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

        $placement = SingularTaxonomySettings::getPlacement((string) $postType);
        $hook = $placement === SingularTaxonomySettings::PLACEMENT_AFTER_CONTENT
            ? 'article_content_after'
            : 'article_content_before';

        add_action($hook, function () use ($postId, $postType): void {
            $this->renderForPost($postId, (string) $postType);
        }, 25);
    }

    public function renderForPost(int $postId, string $postType): void
    {
        $tags = $this->buildTaxonomyTags($postId, $postType);
        TaglistRenderer::render($tags);
    }

    /**
     * @return array<int, array{label: string, href?: string, color?: string}>
     */
    private function buildTaxonomyTags(int $postId, string $postType): array
    {
        $selected = SingularTaxonomySettings::getSelectedTaxonomies($postType);
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
                $tag = ['label' => $term->name];
                $href = $this->getTermHref($term);
                $color = $this->getTermColor($term);

                if ($href !== null) {
                    $tag['href'] = $href;
                }

                if ($color !== null) {
                    $tag['color'] = $color;
                }

                $tags[] = $tag;
            }
        }

        return $tags;
    }

    private function getTermHref(\WP_Term $term): ?string
    {
        $redirect = get_term_meta($term->term_id, 'redirect_to', true);
        if (is_array($redirect) && !empty($redirect['url'])) {
            return (string) $redirect['url'];
        }

        return null;
    }

    /**
     * Term ACF `colour` (Municipio core field), sanitized to a CSS hex value.
     */
    private function getTermColor(\WP_Term $term): ?string
    {
        $color = null;

        if (function_exists('get_field')) {
            $fromAcf = get_field('colour', 'term_' . $term->term_id);
            if (!is_string($fromAcf) || $fromAcf === '') {
                $fromAcf = get_field('colour', $term->taxonomy . '_' . $term->term_id);
            }

            if (is_string($fromAcf) && $fromAcf !== '') {
                $color = $fromAcf;
            }
        }

        if ($color === null) {
            $meta = get_term_meta($term->term_id, 'colour', true);
            if (is_string($meta) && $meta !== '') {
                $color = $meta;
            }
        }

        if (is_string($color) && $color !== '' && !str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        $filtered = apply_filters('Municipio/getTermColour', $color, $term, $term->taxonomy);

        if (!is_string($filtered) || $filtered === '') {
            return null;
        }

        if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $filtered)) {
            return null;
        }

        return $filtered;
    }
}
