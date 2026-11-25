<?php

namespace EslovCustomisation\Customisations;

/**
 * Removes empty paragraphs that wpautop adds around a one-page lead.
 *
 * @upstream-shim id=municipio-one-page-lead-empty-paragraphs
 * @upstream-repo municipio (library/PostObject/Decorators/PostObjectWithFilteredContent.php getFilteredContent)
 * @upstream-broken getFilteredContent calls removeEmptyPTag before the_excerpt. wpautop then wraps leftover <!-- wp:paragraph --> comments in <p>, and HtmlCommentRemoveProcessor leaves <p></p> around <p class="lead">. removeEmptyPTag does not match a paragraph whose only child is a comment. Present since one-page prints getContent() (f1a7fba, 2026-10-06).
 * @upstream-fix-needed After the_excerpt and the_content, strip paragraphs that contain only whitespace, non-breaking spaces, or HTML comments.
 * @remove-when Municipio strips those paragraphs after the_excerpt. Not fixed as of Municipio 7.92.18: getFilteredContent still calls removeEmptyPTag only before the_excerpt. Then delete this class and its App.php registration.
 * @verify-removal Open a one-page with a more-tag lead and no empty paragraph blocks (local /omsorg-stod/aldre/). There is no <p></p> before or after p.lead.
 */
class FilteredContentEmptyParagraphs
{
    public function __construct()
    {
        add_filter('Municipio/PostObject/getContent', [$this, 'removeEmptyParagraphs'], 20);
    }

    /**
     * Strip paragraphs that contain only whitespace, non-breaking spaces, or HTML comments.
     *
     * @param string $content Filtered post content.
     */
    public function removeEmptyParagraphs(string $content): string
    {
        $stripped = preg_replace(
            '/<p\b[^>]*>(?:\s|&nbsp;|<!--.*?-->)*<\/p>/s',
            '',
            $content
        );

        return is_string($stripped) ? $stripped : $content;
    }
}
