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
     * @return array<int, array{label: string, href?: string}>
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

                if ($href !== null) {
                    $tag['href'] = $href;
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
}
