<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Infrastructure;

use BackTo\Framework\Taxonomy\Contracts\TermQueryGatewayInterface;

use function get_terms;

final class WordPressTermQueryGateway implements TermQueryGatewayInterface
{
    /**
     * @param array<string, mixed> $args
     * @return \WP_Term[]
     */
    public function queryTerms(array $args): array
    {
        $result = get_terms($args);

        if ($result instanceof \WP_Error) {
            return [];
        }

        /** @var \WP_Term[] $result */
        return $result;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function countTerms(array $args): int
    {
        $args['fields'] = 'count';
        $result = get_terms($args);

        if ($result instanceof \WP_Error) {
            return 0;
        }

        return (int) $result;
    }
}
