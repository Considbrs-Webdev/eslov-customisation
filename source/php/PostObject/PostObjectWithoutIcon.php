<?php

namespace EslovCustomisation\PostObject;

use Municipio\PostObject\Decorators\AbstractPostObjectDecorator;
use Municipio\PostObject\Icon\IconInterface;
use Municipio\PostObject\PostObjectInterface;

/**
 * Suppresses term/post icons on mod-posts cards (LTS did not resolve term icons).
 *
 * Also restores posts_fields visibility for commentCount: BackwardsCompatiblePostObject
 * __get prefers legacyPost->commentCount over the false set by setPostViewData().
 */
class PostObjectWithoutIcon extends AbstractPostObjectDecorator implements PostObjectInterface
{
    public function __construct(
        PostObjectInterface $postObject,
        private bool $showCommentCount = false,
    ) {
        parent::__construct($postObject);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'commentCount') {
            return $this->showCommentCount
                ? (string) $this->postObject->getCommentCount()
                : false;
        }

        if (property_exists($this->postObject, $name)) {
            return $this->postObject->{$name};
        }

        return parent::__get($name);
    }

    public function getIcon(): ?IconInterface
    {
        return null;
    }
}
