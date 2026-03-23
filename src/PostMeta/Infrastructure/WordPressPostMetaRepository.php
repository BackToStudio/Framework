<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Infrastructure;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaRepositoryInterface;
use BackTo\Framework\PostMeta\Factory\PostMetaFactory;

use function get_post_meta;
use function update_post_meta;
use function delete_post_meta;

class WordPressPostMetaRepository implements PostMetaRepositoryInterface
{
    private readonly PostMetaFactory $factory;

    public function __construct(PostMetaFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create(PostMetaInterface $postMeta): PostMetaInterface
    {
        $result = $this->update($postMeta);

        if ($result === false) {
            throw new \RuntimeException(sprintf(
                'Failed to create post meta "%s" for post %d.',
                $postMeta->getMetaKey(),
                $postMeta->getPostId()
            ));
        }

        return $postMeta;
    }

    public function get(int $postId, string $metaKey, bool $single = true): PostMetaInterface
    {
        if ($postId <= 0) {
            throw new \InvalidArgumentException(\sprintf('Post ID must be positive, got %d.', $postId));
        }

        $postMetaValue = get_post_meta($postId, $metaKey, $single);
        return $this->factory->create($postId, $metaKey, $postMetaValue);
    }

    /**
     * @return int|bool Meta ID on first insert, true on update, false on failure.
     */
    public function update(PostMetaInterface $postMeta): int|bool
    {
        return update_post_meta($postMeta->getPostId(), $postMeta->getMetaKey(), $postMeta->getMetaValue());
    }

    public function delete(PostMetaInterface $postMeta): bool
    {
        return delete_post_meta($postMeta->getPostId(), $postMeta->getMetaKey());
    }
}
