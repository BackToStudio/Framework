<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup\CommentCleanup;
use BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup\OrphanedMetaCleanup;
use BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup\PostCleanup;
use BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup\RevisionCleanup;
use BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup\TransientCleanup;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

/**
 * WordPress adapter for database cleanup and optimization.
 *
 * Delegates cleanup to pluggable CleanupStrategy instances (OCP).
 * Database access goes through DatabaseConnectionInterface (DIP).
 */
final class WordPressDatabaseOptimizer implements DatabaseOptimizerInterface
{
    private readonly DatabaseConnectionInterface $db;

    /** @var CleanupStrategyInterface[] */
    private readonly array $strategies;

    /**
     * @param CleanupStrategyInterface[] $strategies Override default strategies. Pass empty array for defaults.
     */
    public function __construct(
        DatabaseConnectionInterface $db,
        array $strategies = [],
        int $revisionsLimit = 5,
    ) {
        $this->db = $db;
        $this->strategies = $strategies !== [] ? $strategies : self::defaultStrategies($revisionsLimit);
    }

    public function cleanup(): array
    {
        $results = [];

        foreach ($this->strategies as $strategy) {
            $results[$strategy->name()] = $strategy->execute($this->db);
        }

        return $results;
    }

    public function optimizeTables(): int
    {
        $tables = $this->db->getCol(
            $this->db->prepare("SHOW TABLES LIKE %s", $this->db->escLike($this->db->prefix()) . '%')
        );
        $count = 0;

        foreach ($tables as $table) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table) !== 1) {
                continue;
            }
            $this->db->query("OPTIMIZE TABLE `{$table}`");
            $count++;
        }

        return $count;
    }

    /**
     * @return CleanupStrategyInterface[]
     */
    private static function defaultStrategies(int $revisionsLimit): array
    {
        return [
            new RevisionCleanup($revisionsLimit),
            PostCleanup::autoDrafts(),
            PostCleanup::trashed(),
            CommentCleanup::spam(),
            CommentCleanup::trashed(),
            new TransientCleanup(),
            OrphanedMetaCleanup::postMeta(),
            OrphanedMetaCleanup::commentMeta(),
        ];
    }
}
