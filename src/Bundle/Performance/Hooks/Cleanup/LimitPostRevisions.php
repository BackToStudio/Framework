<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cleanup;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Limit the number of post revisions stored in the database.
 *
 * Prevents unbounded database growth from post revisions.
 */
final class LimitPostRevisions implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly int $maxRevisions;

    public function __construct(HookDispatcherInterface $hookDispatcher, int $maxRevisions = 5)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->maxRevisions = $maxRevisions;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('wp_revisions_to_keep', [$this, 'limitRevisions']);
    }

    public function limitRevisions(): int
    {
        return $this->maxRevisions;
    }
}
