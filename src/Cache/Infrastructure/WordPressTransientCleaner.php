<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Infrastructure;

use BackTo\Framework\Cache\Contracts\CacheCleanerInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

/**
 * WordPress adapter for bulk-clearing cache entries via database.
 */
final class WordPressTransientCleaner implements CacheCleanerInterface
{
    private readonly DatabaseConnectionInterface $db;

    public function __construct(DatabaseConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function clearByPrefix(string $prefix): bool
    {
        $options = $this->db->prefix() . 'options';

        $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->db->escLike($prefix) . '%',
                '_transient_timeout_' . $this->db->escLike($prefix) . '%'
            )
        );

        return true;
    }
}
