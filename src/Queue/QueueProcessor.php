<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;

/**
 * Processes all queue groups with a transient-based lock to prevent overlap.
 */
class QueueProcessor
{
    private const LOCK_KEY = 'backto_queue_lock';
    private const LOCK_TIMEOUT = 300;

    private readonly QueueWorker $worker;
    private readonly QueueRegistry $registry;
    private readonly Contracts\QueueRepositoryInterface $repository;
    private readonly TransientStoreInterface $transientStore;

    public function __construct(
        QueueWorker $worker,
        QueueRegistry $registry,
        Contracts\QueueRepositoryInterface $repository,
        TransientStoreInterface $transientStore,
    ) {
        $this->worker = $worker;
        $this->registry = $registry;
        $this->repository = $repository;
        $this->transientStore = $transientStore;
    }

    /**
     * Process all queue groups with a transient-based lock to prevent overlap.
     */
    public function processAllGroups(): void
    {
        if (!$this->acquireLock()) {
            return;
        }

        try {
            $groups = $this->collectGroups();

            foreach ($groups as $group) {
                $this->worker->processQueue($group);
            }
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Collect all unique groups from registered handlers AND active DB jobs.
     *
     * @return string[]
     */
    private function collectGroups(): array
    {
        $groups = ['default'];

        foreach ($this->registry->getJobs() as $job) {
            $group = $job->getGroup();
            if (!\in_array($group, $groups, true)) {
                $groups[] = $group;
            }
        }

        foreach ($this->repository->getActiveGroups() as $group) {
            if (!\in_array($group, $groups, true)) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    protected function acquireLock(): bool
    {
        if ($this->transientStore->get(self::LOCK_KEY)) {
            return false;
        }

        $this->transientStore->set(self::LOCK_KEY, \getmypid() ?: 1, self::LOCK_TIMEOUT);

        return true;
    }

    protected function releaseLock(): void
    {
        $this->transientStore->delete(self::LOCK_KEY);
    }
}
