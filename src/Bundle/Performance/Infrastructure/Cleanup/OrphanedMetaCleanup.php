<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

final class OrphanedMetaCleanup implements CleanupStrategyInterface
{
    private readonly string $metaType;

    private function __construct(string $metaType)
    {
        $this->metaType = $metaType;
    }

    public static function postMeta(): self
    {
        return new self('post');
    }

    public static function commentMeta(): self
    {
        return new self('comment');
    }

    public function name(): string
    {
        return 'orphaned_' . $this->metaType . 'meta';
    }

    public function execute(DatabaseConnectionInterface $db): int
    {
        $prefix = $db->prefix();

        if ($this->metaType === 'post') {
            $metaTable = $prefix . 'postmeta';
            $parentTable = $prefix . 'posts';

            return (int) $db->query(
                "DELETE pm FROM {$metaTable} pm
                LEFT JOIN {$parentTable} p ON p.ID = pm.post_id
                WHERE p.ID IS NULL"
            );
        }

        $metaTable = $prefix . 'commentmeta';
        $parentTable = $prefix . 'comments';

        return (int) $db->query(
            "DELETE cm FROM {$metaTable} cm
            LEFT JOIN {$parentTable} c ON c.comment_ID = cm.comment_id
            WHERE c.comment_ID IS NULL"
        );
    }
}
