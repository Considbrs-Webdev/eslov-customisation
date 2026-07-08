<?php

namespace EslovCustomisation\Migration;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Converts legacy one-page post content into block markup understood by Municipio.
 */
class OnePageContentConverter
{
    private const BUTTON_FIELDS = [
        'text'               => 'field_60acdb2b56c3f',
        'link'               => 'field_60acdb4756c40',
        'open_in_new_window' => 'field_698c35b2385a0',
        'color'              => 'field_60acdad256c3e',
        'style'              => 'field_60acdb5956c41',
        'size'               => 'field_60acdba356c42',
    ];

    /**
     * Converts a content string into serialized block markup.
     *
     * @param string $content Classic editor post content.
     * @return array{status:string,content:string,warnings:array<int,string>,stats:array<string,int>}
     */
    public function convert(string $content): array
    {
        $warnings = [];
        $stats    = $this->emptyStats();
        $content  = $this->normalizeContent($content);

        if ($this->hasMalformedHints($content)) {
            $warnings[] = 'Content contains known malformed legacy markup hints; verify converted output manually.';
        }

        if ($this->isJunkOnly($content)) {
            return [
                'status'   => 'junk',
                'content'  => '',
                'warnings' => ['Content is empty or junk-only after trimming non-breaking spaces.'],
                'stats'    => $stats,
            ];
        }

        $blocks = [];
        $parts  = preg_split('/(<!--\s*more\s*-->)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts ?: [] as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (preg_match('/^<!--\s*more\s*-->$/i', $part)) {
                $blocks[] = $this->moreBlock();
                $stats['more']++;
                continue;
            }

            $fragmentBlocks = $this->convertFragment($part, $warnings, $stats);
            array_push($blocks, ...$fragmentBlocks);
        }

        $serialized = trim(implode("\n\n", array_filter($blocks)));

        if ($serialized === '') {
            return [
                'status'   => 'failed',
                'content'  => '',
                'warnings' => array_merge($warnings, ['Conversion produced no blocks.']),
                'stats'    => $stats,
            ];
        }

        if (!has_blocks($serialized)) {
            return [
                'status'   => 'failed',
                'content'  => '',
                'warnings' => array_merge($warnings, ['Serialized content does not contain block delimiters.']),
                'stats'    => $stats,
            ];
        }

        return [
            'status'   => empty($warnings) ? 'converted' : 'converted_with_warnings',
            'content'  => $serialized,
            'warnings' => $warnings,
            'stats'    => $stats,
        ];
    }

    /**
     * Classifies legacy content without requiring a write.
     *
     * @param string $content Classic editor post content.
     * @return array<string,bool>
     */
    public function classify(string $content): array
    {
        $content = $this->normalizeContent($content);

        return [
            'junk'       => $this->isJunkOnly($content),
            'more'       => (bool) preg_match('/<!--\s*more\s*-->/i', $content),
            'button'     => (bool) preg_match('/\bc-button\b|\bbtn-theme-|\bclass=(["\'])[^"\']*\bbtn\b/i', $content),
            'shortcode'  => (bool) preg_match('/\[[A-Za-z][A-Za-z0-9_-]*(?:\s+[^\]]*)?\]/', $content),
            'image'      => (bool) preg_match('/<img\b/i', $content),
            'list'       => (bool) preg_match('/<[ou]l\b/i', $content),
            'heading'    => (bool) preg_match('/<h[1-6]\b/i', $content),
            'table'      => (bool) preg_match('/<table\b/i', $content),
            'embed'      => (bool) preg_match('/<(iframe|embed|video|audio)\b/i', $content),
            'script'     => (bool) preg_match('/<script\b/i', $content),
            'inline_css' => (bool) preg_match('/\sstyle=|<style\b/i', $content),
            'malformed'  => $this->hasMalformedHints($content),
        ];
    }

    /**
     * @return array<string,int>
     */
    private function emptyStats(): array
    {
        return [
            'paragraph' => 0,
            'heading'   => 0,
            'more'      => 0,
            'list'      => 0,
            'shortcode' => 0,
            'button'    => 0,
            'image'     => 0,
            'freeform'  => 0,
        ];
    }

    private function normalizeContent(string $content): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $content));
    }

    private function isJunkOnly(string $content): bool
    {
        $withoutComments = preg_replace('/<!--.*?-->/s', '', $content) ?? $content;
        $text            = html_entity_decode(wp_strip_all_tags($withoutComments), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text            = preg_replace('/\x{00a0}|&nbsp;|\s/u', '', $text) ?? '';

        return $text === '';
    }

    /**
     * @param array<int,string> $warnings
     * @param array<string,int> $stats
     * @return array<int,string>
     */
    private function convertFragment(string $html, array &$warnings, array &$stats): array
    {
        $normalizedHtml = trim(wpautop($html));
        $document       = new DOMDocument('1.0', 'UTF-8');
        $previous       = libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><html><body><div id="eslov-fragment">' . $normalizedHtml . '</div></body></html>',
            LIBXML_HTML_NODEFDTD
        );

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            $warnings[] = 'Could not parse fragment as HTML; preserved as freeform.';
            return [$this->freeformBlock($html, $stats)];
        }

        foreach ($errors as $error) {
            if ($error->level >= LIBXML_ERR_ERROR) {
                $warnings[] = 'HTML parser reported malformed markup; verify freeform fallbacks.';
                break;
            }
        }

        $wrapper = $document->getElementById('eslov-fragment');

        if (!$wrapper) {
            $warnings[] = 'Could not locate parser wrapper; preserved as freeform.';
            return [$this->freeformBlock($html, $stats)];
        }

        $blocks = [];

        foreach ($wrapper->childNodes as $node) {
            array_push($blocks, ...$this->convertNode($node, $warnings, $stats));
        }

        return $blocks;
    }

    /**
     * @param array<int,string> $warnings
     * @param array<string,int> $stats
     * @return array<int,string>
     */
    private function convertNode(DOMNode $node, array &$warnings, array &$stats): array
    {
        if ($node instanceof DOMText) {
            return $this->convertText($node->wholeText, $stats, $warnings);
        }

        if ($node instanceof DOMComment) {
            if (trim($node->nodeValue) === 'more') {
                $stats['more']++;
                return [$this->moreBlock()];
            }

            return [];
        }

        if (!$node instanceof DOMElement) {
            return [];
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'p') {
            return $this->convertParagraph($node, $warnings, $stats);
        }

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            return [$this->headingBlock($node, (int) $matches[1], $warnings, $stats)];
        }

        if ($tag === 'ul' || $tag === 'ol') {
            return [$this->listBlock($node, $tag === 'ol', $stats)];
        }

        if ($tag === 'a' && $this->isButtonElement($node)) {
            return [$this->buttonBlock($node, $stats)];
        }

        if ($tag === 'img') {
            return [$this->imageOrFreeformBlock($node, $warnings, $stats)];
        }

        if ($tag === 'figure') {
            return [$this->imageOrFreeformBlock($node, $warnings, $stats)];
        }

        if ($tag === 'hr') {
            $warnings[] = 'Preserved <hr> as freeform because core/separator is not allowed by Modularity.';
            return [$this->freeformBlock($this->outerHtml($node), $stats)];
        }

        if (in_array($tag, ['div', 'section', 'article', 'table', 'iframe', 'script', 'style'], true)) {
            $warnings[] = sprintf('Preserved <%s> fragment as freeform.', $tag);
            return [$this->freeformBlock($this->outerHtml($node), $stats)];
        }

        return [$this->paragraphBlock($this->outerHtml($node), $stats)];
    }

    /**
     * @param array<string,int> $stats
     * @return array<int,string>
     */
    private function convertText(string $text, array &$stats, array &$warnings): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $shortcodes = [];

        if ($this->isShortcodeOnly($text, $shortcodes)) {
            return array_map(function (string $shortcode) use (&$stats, &$warnings): string {
                return $this->shortcodeBlock($shortcode, $stats, $warnings);
            }, $shortcodes);
        }

        return [$this->paragraphBlock(esc_html($text), $stats)];
    }

    /**
     * @param array<int,string> $warnings
     * @param array<string,int> $stats
     * @return array<int,string>
     */
    private function convertParagraph(DOMElement $paragraph, array &$warnings, array &$stats): array
    {
        $innerHtml = trim($this->innerHtml($paragraph));
        $text      = trim($paragraph->textContent);

        if ($innerHtml === '' || !$this->hasMeaningfulText($text)) {
            return [];
        }

        $shortcodes = [];

        if ($this->isShortcodeOnly($innerHtml, $shortcodes)) {
            return array_map(function (string $shortcode) use (&$stats, &$warnings): string {
                return $this->shortcodeBlock($shortcode, $stats, $warnings);
            }, $shortcodes);
        }

        if (!$this->paragraphContainsButton($paragraph)) {
            return [$this->paragraphBlock($innerHtml, $stats)];
        }

        $blocks = [];
        $buffer = '';

        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'a' && $this->isButtonElement($child)) {
                $this->flushParagraphBuffer($buffer, $blocks, $stats);
                $blocks[] = $this->buttonBlock($child, $stats);
                continue;
            }

            $buffer .= $this->nodeHtml($child);
        }

        $this->flushParagraphBuffer($buffer, $blocks, $stats);

        if (empty($blocks)) {
            $warnings[] = 'Paragraph contained button markup but produced no semantic blocks; preserved as freeform.';
            return [$this->freeformBlock($this->outerHtml($paragraph), $stats)];
        }

        return $blocks;
    }

    /**
     * @param array<int,string> $blocks
     * @param array<string,int> $stats
     */
    private function flushParagraphBuffer(string &$buffer, array &$blocks, array &$stats): void
    {
        $html = trim($buffer);
        $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $buffer = '';

        if ($html === '' || !$this->hasMeaningfulText($text)) {
            return;
        }

        $blocks[] = $this->paragraphBlock($html, $stats);
    }

    private function moreBlock(): string
    {
        return "<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->";
    }

    /**
     * @param array<string,int> $stats
     */
    private function paragraphBlock(string $innerHtml, array &$stats): string
    {
        $stats['paragraph']++;

        return get_comment_delimited_block_content(
            'core/paragraph',
            [],
            '<p>' . trim($innerHtml) . '</p>'
        );
    }

    /**
     * @param array<int,string> $warnings
     * @param array<string,int> $stats
     */
    private function headingBlock(DOMElement $element, int $level, array &$warnings, array &$stats): string
    {
        if ($level === 1) {
            $warnings[] = 'Converted legacy H1 in body content; verify heading hierarchy.';
        }

        $stats['heading']++;
        $attrs = ['level' => $level];

        if ($element->hasAttribute('id')) {
            $attrs['anchor'] = sanitize_title($element->getAttribute('id'));
        }

        if ($element->hasAttribute('class')) {
            $attrs['className'] = trim($element->getAttribute('class'));
        }

        $class = 'wp-block-heading';
        if ($element->hasAttribute('class')) {
            $class .= ' ' . trim($element->getAttribute('class'));
        }

        $id = $element->hasAttribute('id') ? ' id="' . esc_attr($element->getAttribute('id')) . '"' : '';

        return get_comment_delimited_block_content(
            'core/heading',
            $attrs,
            sprintf('<h%d%s class="%s">%s</h%d>', $level, $id, esc_attr($class), trim($this->innerHtml($element)), $level)
        );
    }

    /**
     * @param array<string,int> $stats
     */
    private function listBlock(DOMElement $element, bool $ordered, array &$stats): string
    {
        $stats['list']++;
        $attrs = $ordered ? ['ordered' => true] : [];

        return get_comment_delimited_block_content('core/list', $attrs, $this->outerHtml($element));
    }

    /**
     * @param array<string,int> $stats
     */
    /**
     * @param array<string,int> $stats
     * @param array<int,string> $warnings
     */
    private function shortcodeBlock(string $shortcode, array &$stats, array &$warnings): string
    {
        $stats['shortcode']++;

        if (preg_match('/\[modularity\s+[^\]]*id=(["\']?)(\d+)\1/i', $shortcode, $matches)) {
            $moduleId = (int) $matches[2];

            if (!get_post($moduleId)) {
                $warnings[] = sprintf('Shortcode references missing Modularity post %d.', $moduleId);
            }
        }

        return get_comment_delimited_block_content('core/shortcode', [], trim($shortcode));
    }

    /**
     * @param array<string,int> $stats
     */
    private function buttonBlock(DOMElement $element, array &$stats): string
    {
        $stats['button']++;

        $classes = ' ' . preg_replace('/\s+/', ' ', $element->getAttribute('class')) . ' ';
        $text    = $this->extractButtonText($element);
        $href    = $element->getAttribute('href');
        $style   = 'filled';
        $color   = 'default';
        $size    = 'md';

        if (str_contains($classes, ' c-button__outlined ')) {
            $style = 'outlined';
        } elseif (str_contains($classes, ' c-button__basic ')) {
            $style = 'basic';
        }

        foreach (['primary', 'secondary', 'default'] as $candidate) {
            if (preg_match('/--' . preg_quote($candidate, '/') . '\b/', $classes)) {
                $color = $candidate;
                break;
            }
        }

        foreach (['sm', 'md', 'lg'] as $candidate) {
            if (str_contains($classes, ' c-button--' . $candidate . ' ')) {
                $size = $candidate;
                break;
            }
        }

        $data = [
            'text'               => $text,
            '_text'              => self::BUTTON_FIELDS['text'],
            'link'               => $href,
            '_link'              => self::BUTTON_FIELDS['link'],
            'open_in_new_window' => $element->getAttribute('target') === '_blank' ? 1 : 0,
            '_open_in_new_window' => self::BUTTON_FIELDS['open_in_new_window'],
            'color'              => $color,
            '_color'             => self::BUTTON_FIELDS['color'],
            'style'              => $style,
            '_style'             => self::BUTTON_FIELDS['style'],
            'size'               => $size,
            '_size'              => self::BUTTON_FIELDS['size'],
        ];

        return get_comment_delimited_block_content(
            'acf/button',
            [
                'name' => 'acf/button',
                'data' => $data,
                'mode' => 'auto',
            ],
            ''
        );
    }

    private function extractButtonText(DOMElement $element): string
    {
        $xpath = new \DOMXPath($element->ownerDocument);
        $nodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " c-button__label-text ")]', $element);

        if ($nodes && $nodes->length > 0) {
            return trim((string) $nodes->item(0)->textContent);
        }

        return trim($element->textContent);
    }

    /**
     * @param array<int,string> $warnings
     * @param array<string,int> $stats
     */
    private function imageOrFreeformBlock(DOMElement $element, array &$warnings, array &$stats): string
    {
        $image = strtolower($element->tagName) === 'img' ? $element : $this->firstImage($element);

        if (!$image) {
            $warnings[] = 'Preserved image wrapper as freeform because no image tag was found.';
            return $this->freeformBlock($this->outerHtml($element), $stats);
        }

        $html = $this->outerHtml($element);

        if (strtolower($element->tagName) !== 'img') {
            $warnings[] = 'Preserved linked or wrapped image as freeform to avoid losing link/caption semantics.';
            return $this->freeformBlock($html, $stats);
        }

        $attachmentId = $this->extractAttachmentId($image);

        if (!$attachmentId) {
            $warnings[] = 'Preserved image as freeform because no wp-image-* attachment id was found.';
            return $this->freeformBlock($html, $stats);
        }

        $stats['image']++;

        return get_comment_delimited_block_content(
            'core/image',
            ['id' => $attachmentId],
            $html
        );
    }

    private function extractAttachmentId(DOMElement $image): ?int
    {
        if (!preg_match('/\bwp-image-(\d+)\b/', $image->getAttribute('class'), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function firstImage(DOMElement $element): ?DOMElement
    {
        $images = $element->getElementsByTagName('img');

        if ($images->length === 0) {
            return null;
        }

        $image = $images->item(0);

        return $image instanceof DOMElement ? $image : null;
    }

    /**
     * @param array<string,int> $stats
     */
    private function freeformBlock(string $html, array &$stats): string
    {
        $stats['freeform']++;

        return get_comment_delimited_block_content('core/freeform', [], trim($html));
    }

    /**
     * @param array<int,string>|null $shortcodes
     */
    private function isShortcodeOnly(string $html, ?array &$shortcodes = null): bool
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($text === '') {
            return false;
        }

        $pattern = '/\[[A-Za-z][A-Za-z0-9_-]*(?:\s+[^\]]*)?\](?:.*?\[\/[A-Za-z][A-Za-z0-9_-]*\])?/s';
        preg_match_all($pattern, $text, $matches);

        $shortcodes = array_values(array_filter(array_map('trim', $matches[0] ?? [])));

        if (empty($shortcodes)) {
            return false;
        }

        $remaining = trim(str_replace($shortcodes, '', $text));

        return $remaining === '';
    }

    private function paragraphContainsButton(DOMElement $paragraph): bool
    {
        foreach ($paragraph->getElementsByTagName('a') as $anchor) {
            if ($anchor instanceof DOMElement && $this->isButtonElement($anchor)) {
                return true;
            }
        }

        return false;
    }

    private function isButtonElement(DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'a') {
            return false;
        }

        $classes = ' ' . preg_replace('/\s+/', ' ', $element->getAttribute('class')) . ' ';

        return str_contains($classes, ' c-button ') ||
            str_contains($classes, ' btn ') ||
            str_contains($classes, ' btn-theme-');
    }

    private function hasMalformedHints(string $content): bool
    {
        return (bool) preg_match('/target="_blank&quot;|x_elementToProof|h-svdefaultanchor|href="[^"]*felanmalan/i', $content);
    }

    private function hasMeaningfulText(string $text): bool
    {
        $text = preg_replace('/\x{00a0}|&nbsp;|\s/u', '', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';

        return $text !== '';
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $this->nodeHtml($child);
        }

        return $html;
    }

    private function outerHtml(DOMNode $node): string
    {
        return $node->ownerDocument ? (string) $node->ownerDocument->saveHTML($node) : '';
    }

    private function nodeHtml(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return $node->wholeText;
        }

        return $this->outerHtml($node);
    }
}
