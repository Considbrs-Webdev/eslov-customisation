<?php

namespace EslovCustomisation\Modules\Navigation;

use ComponentLibrary\Integrations\Image\Image as ImageComponentContract;
use Modularity\Integrations\Component\ImageFocusResolver;
use Modularity\Integrations\Component\ImageResolver;
use Municipio\Helper\Image as ImageHelper;

class ImageAdapter
{
    /**
     * @return ImageComponentContract|array<string, mixed>|null
     */
    public function fromAttachmentId(int $attachmentId, string $context = 'card'): ImageComponentContract|array|null
    {
        if ($attachmentId <= 0) {
            return null;
        }

        if ($context === 'card' && class_exists(ImageComponentContract::class)) {
            return ImageComponentContract::factory(
                $attachmentId,
                // Allow responsive variants above the library's first 425px step.
                [1024, false],
                new ImageResolver(),
                new ImageFocusResolver(['id' => $attachmentId]),
            );
        }

        $size = $context === 'tree' ? [400, false] : [400, 225];
        $image = ImageHelper::getImageAttachmentData($attachmentId, $size);

        if (!$image) {
            return null;
        }

        unset($image['title'], $image['description']);
        $image['removeCaption'] = true;

        if ($context === 'tree') {
            $srcset = $this->treeSrcset($attachmentId);

            if ($srcset !== null) {
                $image['srcset'] = $srcset;
            }
        }

        return $image;
    }

    /**
     * Srcset from the 200-wide and 400-wide tree requests.
     *
     * The descriptor is the width of the file that was actually returned.
     * WordPress reports the requested size even when it served a different
     * intermediate, which would make the browser pick the wrong candidate.
     */
    private function treeSrcset(int $attachmentId): ?string
    {
        $candidates = [];

        foreach ([[200, false], [400, false]] as $size) {
            $image = wp_get_attachment_image_src($attachmentId, $size);

            if (!is_array($image) || empty($image[0])) {
                continue;
            }

            $width = $this->deliveredWidth($attachmentId, $image[0]);

            if ($width === null || $width < 1) {
                continue;
            }

            $candidates[$width] = $image[0];
        }

        if (count($candidates) < 2) {
            return null;
        }

        ksort($candidates);

        $parts = [];

        foreach ($candidates as $width => $url) {
            $parts[] = $url . ' ' . $width . 'w';
        }

        return implode(', ', $parts);
    }

    /**
     * Pixel width of a resized file, from its filename or attachment metadata.
     */
    private function deliveredWidth(int $attachmentId, string $url): ?int
    {
        $path = parse_url($url, PHP_URL_PATH);
        $basename = wp_basename(is_string($path) && $path !== '' ? $path : $url);

        if (preg_match('/-(\d+)x\d+\.[^.]+$/', $basename, $matches) === 1) {
            return (int) $matches[1];
        }

        $meta = wp_get_attachment_metadata($attachmentId);

        if (!is_array($meta) || empty($meta['width'])) {
            return null;
        }

        $attached = wp_basename((string) get_attached_file($attachmentId));

        if ($basename === $attached) {
            return (int) $meta['width'];
        }

        return null;
    }

    public function fromPost(?\WP_Post $post, string $context = 'card'): ImageComponentContract|array|null
    {
        if (!$post) {
            return null;
        }

        return $this->fromAttachmentId((int) get_post_thumbnail_id($post), $context);
    }
}
