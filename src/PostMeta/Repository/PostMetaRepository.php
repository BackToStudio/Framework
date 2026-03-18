<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Repository;

use BackTo\Framework\PostMeta\Contracts\PostMetaRepositoryInterface;
use BackTo\Framework\PostMeta\Infrastructure\WordPressPostMetaRepository;

/**
 * @deprecated Use PostMetaRepositoryInterface instead. This class will be removed in a future version.
 */
final class PostMetaRepository extends WordPressPostMetaRepository implements PostMetaRepositoryInterface
{
}
