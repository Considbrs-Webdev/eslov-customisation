<?php

/**
 * Renders a "back to section start page" link above article content on child pages.
 */

namespace EslovCustomisation\Customisations;

class SectionStartPageLink
{
    private const ONE_PAGE_TEMPLATE = 'one-page.blade.php';

    public function __construct()
    {
        add_action('article_content_before', [$this, 'render'], 10);
        add_action('inner_loop_start', [$this, 'renderOnOnePage'], 10);
    }

    /**
     * One Page skips the article partial, so print via inner_loop_start instead.
     */
    public function renderOnOnePage(): void
    {
        if (get_page_template_slug() !== self::ONE_PAGE_TEMPLATE) {
            return;
        }

        $this->render();
    }

    /**
     * Output the section start page link if an ancestor is marked as one.
     */
    public function render(): void
    {
        if (!is_singular('page') || !function_exists('get_field')) {
            return;
        }

        $postId = get_the_ID();
        if (!$postId) {
            return;
        }

        $sectionStart = $this->findSectionStartPage($postId);
        if (!$sectionStart) {
            return;
        }

        if (!function_exists('render_blade_view')) {
            return;
        }

        echo render_blade_view('partials.section-start-link', $sectionStart);
    }

    /**
     * Walk up the post_parent chain to find the nearest section start page.
     *
     * @return array{title: string, href: string}|null
     */
    private function findSectionStartPage(int $postId): ?array
    {
        $currentId = (int) wp_get_post_parent_id($postId);

        while ($currentId > 0) {
            if (get_field('page_navigation_section_start_page', $currentId)) {
                return [
                    'title' => get_the_title($currentId),
                    'href' => (string) get_permalink($currentId),
                ];
            }

            $currentId = (int) wp_get_post_parent_id($currentId);
        }

        return null;
    }
}
