<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Anti-corruption layer: PostMeta's view of a Post.
 *
 * This interface defines the minimal contract PostMeta needs
 * from a Post, without depending on the PostType bounded context.
 */
interface PostReferenceInterface
{
    public function getId(): ?int;
}
