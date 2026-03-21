<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Infrastructure;

use BackTo\Framework\PostType\Contracts\PostQueryGatewayInterface;

use function get_posts;

final class WordPressPostQueryGateway implements PostQueryGatewayInterface
{
    /**
     * @param array<string, mixed> $args
     * @return \WP_Post[]
     */
    public function queryPosts(array $args): array
    {
        /** @var \WP_Post[] */
        return get_posts($args);
    }
}
