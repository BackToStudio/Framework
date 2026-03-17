<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;

/**
 * Orchestrates the queue system lifecycle and cron-based processing.
 *
 * - Creates the jobs table on activation.
 * - Registers WP-Cron events to process, rescue, and cleanup jobs.
 * - Uses a transient-based lock to prevent concurrent cron execution.
 */
class RegisterQueue implements Hooks, ActivationHooks, DeactivationHooks
{
    private const CRON_HOOK = 'backto_queue_process';
    private const RESCUE_HOOK = 'backto_queue_rescue';
    private const CLEANUP_HOOK = 'backto_queue_cleanup';
    private const SCHEDULE_INTERVAL = 'every_minute';
    private const LOCK_KEY = 'backto_queue_lock';
    private const LOCK_TIMEOUT = 300;

    private QueueRepositoryInterface $repository;
    private QueueWorker $worker;
    private QueueRegistry $registry;
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(
        QueueRepositoryInterface $repository,
        QueueWorker $worker,
        QueueRegistry $registry,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->repository = $repository;
        $this->worker = $worker;
        $this->registry = $registry;
        $this->hookDispatcher = $hookDispatcher;
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
        if (!\wp_next_scheduled(self::CRON_HOOK)) {
            \wp_schedule_event(\time(), self::SCHEDULE_INTERVAL, self::CRON_HOOK);
        }

        if (!\wp_next_scheduled(self::RESCUE_HOOK)) {
            \wp_schedule_event(\time(), 'hourly', self::RESCUE_HOOK);
        }

        if (!\wp_next_scheduled(self::CLEANUP_HOOK)) {
            \wp_schedule_event(\time(), 'daily', self::CLEANUP_HOOK);
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
        if (!\wp_next_scheduled(self::CRON_HOOK)) {
            \wp_schedule_event(\time(), self::SCHEDULE_INTERVAL, self::CRON_HOOK);
        }

        if (!\wp_next_scheduled(self::RESCUE_HOOK)) {
            \wp_schedule_event(\time(), 'hourly', self::RESCUE_HOOK);
        }

        if (!\wp_next_scheduled(self::CLEANUP_HOOK)) {
            \wp_schedule_event(\time(), 'daily', self::CLEANUP_HOOK);
        }
    }

    private function unscheduleCronEvents(): void
    {
        \wp_clear_scheduled_hook(self::CRON_HOOK);
        \wp_clear_scheduled_hook(self::RESCUE_HOOK);
        \wp_clear_scheduled_hook(self::CLEANUP_HOOK);
    }

    protected function acquireLock(): bool
    {
        if (\get_transient(self::LOCK_KEY)) {
            return false;
        }

        \set_transient(self::LOCK_KEY, \getmypid() ?: 1, self::LOCK_TIMEOUT);

        return true;
    }

    protected function releaseLock(): void
    {
        \delete_transient(self::LOCK_KEY);
    }
}
