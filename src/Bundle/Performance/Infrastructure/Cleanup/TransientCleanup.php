<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

final class TransientCleanup implements CleanupStrategyInterface
{
    public function name(): string
    {
        return 'expired_transients';
    }

    public function execute(DatabaseConnectionInterface $db): int
    {
        $options = $db->prefix() . 'options';

        return (int) $db->query(
            $db->prepare(
                "DELETE a, b FROM {$options} a
                INNER JOIN {$options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                WHERE a.option_name LIKE %s
                AND b.option_value < %d",
                $db->escLike('_transient_') . '%',
                time()
            )
        );
    }
}
