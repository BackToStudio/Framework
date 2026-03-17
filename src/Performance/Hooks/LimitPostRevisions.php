<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Limit the number of post revisions stored in the database.
 *
 * Prevents unbounded database growth from post revisions.
 */
class LimitPostRevisions implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;
    private int $maxRevisions;

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
