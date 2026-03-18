<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Repository;

use BackTo\Framework\PostType\Contracts\PostRepositoryInterface;
use BackTo\Framework\PostType\Infrastructure\WordPressPostRepository;

/**
 * @deprecated Use PostRepositoryInterface instead. This class will be removed in a future version.
 */
final class PostRepository extends WordPressPostRepository implements PostRepositoryInterface
{
}
