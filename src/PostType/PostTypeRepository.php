<?php

namespace Fantassin\Core\WordPress\PostType;

/**
 * @deprecated
 */
class PostTypeRepository
{

    public function __construct(){
        trigger_error('Fantassin\Core\WordPress\PostType\PostTypeRepository is deprecated, use Fantassin\Core\WordPress\PostType\PostTypeRegistry instead.', E_USER_DEPRECATED);
    }

    /**
     * @var PostTypeInterface[]
     */
    private $postTypes = [];

    /**
     * @param PostTypeInterface $postType
     *
     * @return PostTypeRepository
     */
    public function add(PostTypeInterface $postType): PostTypeRepository
    {
        $this->postTypes[] = $postType;

        return $this;
    }

    /**
     * @return PostTypeInterface[]
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }
}
