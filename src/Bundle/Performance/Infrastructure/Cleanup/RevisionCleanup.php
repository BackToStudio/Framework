<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

final class RevisionCleanup implements CleanupStrategyInterface
{
    private readonly int $limit;

    public function __construct(int $limit = 5)
    {
        $this->limit = $limit;
    }

    public function name(): string
    {
        return 'revisions';
    }

    public function execute(DatabaseConnectionInterface $db): int
    {
        if ($this->limit <= 0) {
            return 0;
        }

        $prefix = $db->prefix();
        $posts = $prefix . 'posts';

        $revisionIds = $db->getCol(
            $db->prepare(
                "SELECT r.ID FROM {$posts} r
                INNER JOIN (
                    SELECT post_parent, ID FROM {$posts}
                    WHERE post_type = 'revision'
                    ORDER BY post_date DESC
                ) ranked ON r.ID = ranked.ID
                WHERE r.post_type = 'revision'
                AND r.ID NOT IN (
                    SELECT sub.ID FROM (
                        SELECT ID, post_parent,
                        ROW_NUMBER() OVER (PARTITION BY post_parent ORDER BY post_date DESC) AS rn
                        FROM {$posts}
                        WHERE post_type = 'revision'
                    ) sub WHERE sub.rn <= %d
                )",
                $this->limit
            )
        );

        if ($revisionIds === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($revisionIds), '%d'));
        $db->query($db->prepare("DELETE FROM {$posts} WHERE ID IN ({$placeholders})", ...$revisionIds));

        return count($revisionIds);
    }
}
