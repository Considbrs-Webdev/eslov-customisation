<?php

namespace EslovCustomisation\Customisations;

/**
 * Remove empty paragraphs that wpautop adds around a one-page lead.
 *
 * one-page.blade.php prints PostObject::getContent() directly under the H1.
 * When the post has a more tag, PostObjectWithFilteredContent marks the first
 * paragraph as `<p class="lead">` and then runs `the_excerpt`. wpautop wraps
 * the leftover `<!-- wp:paragraph -->` comments in their own paragraphs.
 * The theme strips HTML comments afterwards, which leaves `<p></p>` and the
 * gap under the title.
 *
 * The theme's removeEmptyPTag runs on the raw content, before `the_excerpt`,
 * and its pattern does not match a paragraph whose only child is a comment.
 * This filter runs on `Municipio/PostObject/getContent`, after `the_excerpt`
 * and `the_content`.
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
