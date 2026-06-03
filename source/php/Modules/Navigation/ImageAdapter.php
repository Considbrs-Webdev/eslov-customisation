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
                [400, false],
                new ImageResolver(),
                new ImageFocusResolver(['id' => $attachmentId]),
            );
        }

        $size = $context === 'tree' ? [200, false] : [400, 225];
        $image = ImageHelper::getImageAttachmentData($attachmentId, $size);

        if (!$image) {
            return null;
        }

        unset($image['title'], $image['description']);
        $image['removeCaption'] = true;

        return $image;
    }

    public function fromPost(?\WP_Post $post, string $context = 'card'): ImageComponentContract|array|null
    {
        if (!$post) {
            return null;
        }

        return $this->fromAttachmentId((int) get_post_thumbnail_id($post), $context);
    }
}
