<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;

/**
 * Orchestrates the queue system lifecycle and cron-based processing.
 *
 * - Creates the jobs table on activation.
 * - Registers WP-Cron events to process, rescue, and cleanup jobs.
 * - Uses a transient-based lock to prevent concurrent cron execution.
 */
final class RegisterQueue implements Hooks, ActivationHooks, DeactivationHooks
{
    private const CRON_HOOK = 'backto_queue_process';
    private const RESCUE_HOOK = 'backto_queue_rescue';
    private const CLEANUP_HOOK = 'backto_queue_cleanup';
    private const SCHEDULE_INTERVAL = 'every_minute';
    private const LOCK_KEY = 'backto_queue_lock';
    private const LOCK_TIMEOUT = 300;

    private readonly QueueRepositoryInterface $repository;
    private readonly QueueWorker $worker;
    private readonly QueueRegistry $registry;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly CronSchedulerInterface $cronScheduler;
    private readonly TransientStoreInterface $transientStore;

    public function __construct(
        QueueRepositoryInterface $repository,
        QueueWorker $worker,
        QueueRegistry $registry,
        HookDispatcherInterface $hookDispatcher,
        CronSchedulerInterface $cronScheduler,
        TransientStoreInterface $transientStore,
    ) {
        $this->repository = $repository;
        $this->worker = $worker;
        $this->registry = $registry;
        $this->hookDispatcher = $hookDispatcher;
        $this->cronScheduler = $cronScheduler;
        $this->transientStore = $transientStore;
    }

    public function activate(): void
    {
        $this->repository->createTable();
        $this->scheduleCronEvents();
    }

    public function deactivate(): void
    {
        $this->unscheduleCronEvents();
    }

    /**
     * Clean up all queue data on plugin uninstall.
     *
     * Call this from your uninstall.php or register_uninstall_hook callback
     * to remove the jobs table and any transients created by the queue.
     */
    public function uninstall(): void
    {
        $this->repository->dropTable();
        $this->transientStore->delete(self::LOCK_KEY);
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('cron_schedules', [$this, 'registerCronSchedule']);
        $this->hookDispatcher->addAction('init', [$this, 'ensureCronScheduled']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'processAllGroups']);
        $this->hookDispatcher->addAction(self::RESCUE_HOOK, [$this, 'rescueStuckJobs']);
        $this->hookDispatcher->addAction(self::CLEANUP_HOOK, [$this, 'cleanupJobs']);
    }

    /**
     * Register the "every minute" cron schedule.
     *
     * @param array<string, array{interval: int, display: string}> $schedules
     *
     * @return array<string, array{interval: int, display: string}>
     */
    public function registerCronSchedule(array $schedules): array
    {
        if (!isset($schedules[self::SCHEDULE_INTERVAL])) {
            $schedules[self::SCHEDULE_INTERVAL] = [
                'interval' => 60,
                'display' => 'Every Minute',
            ];
        }

        return $schedules;
    }

    /**
     * Ensure cron events are scheduled (idempotent).
     */
    public function ensureCronScheduled(): void
    {
        if (!$this->cronScheduler->isScheduled(self::CRON_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CRON_HOOK, self::SCHEDULE_INTERVAL);
        }

        if (!$this->cronScheduler->isScheduled(self::RESCUE_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::RESCUE_HOOK, 'hourly');
        }

        if (!$this->cronScheduler->isScheduled(self::CLEANUP_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CLEANUP_HOOK, 'daily');
        }
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
     * Rescue jobs stuck in running state.
     */
    public function rescueStuckJobs(): void
    {
        $this->repository->rescueStuck(300);
    }

    /**
     * Clean up completed jobs (>24h) and exhausted failed jobs (>7 days).
     */
    public function cleanupJobs(): void
    {
        $this->repository->cleanup(86400);
        $this->repository->cleanupFailed(604800);
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

    private function scheduleCronEvents(): void
    {
        if (!$this->cronScheduler->isScheduled(self::CRON_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CRON_HOOK, self::SCHEDULE_INTERVAL);
        }

        if (!$this->cronScheduler->isScheduled(self::RESCUE_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::RESCUE_HOOK, 'hourly');
        }

        if (!$this->cronScheduler->isScheduled(self::CLEANUP_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CLEANUP_HOOK, 'daily');
        }
    }

    private function unscheduleCronEvents(): void
    {
        $this->cronScheduler->clear(self::CRON_HOOK);
        $this->cronScheduler->clear(self::RESCUE_HOOK);
        $this->cronScheduler->clear(self::CLEANUP_HOOK);
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
