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
 * Delegates actual work to focused collaborators:
 * - QueueProcessor: processes queue groups with locking
 * - QueueMaintenance: rescues stuck jobs and cleans up old ones
 */
final class RegisterQueue implements Hooks, ActivationHooks, DeactivationHooks
{
    private const CRON_HOOK = 'backto_queue_process';
    private const RESCUE_HOOK = 'backto_queue_rescue';
    private const CLEANUP_HOOK = 'backto_queue_cleanup';
    private const SCHEDULE_INTERVAL = 'every_minute';
    private const LOCK_KEY = 'backto_queue_lock';

    private readonly QueueRepositoryInterface $repository;
    private readonly QueueProcessor $processor;
    private readonly QueueMaintenance $maintenance;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly CronSchedulerInterface $cronScheduler;
    private readonly TransientStoreInterface $transientStore;

    public function __construct(
        QueueRepositoryInterface $repository,
        QueueProcessor $processor,
        QueueMaintenance $maintenance,
        HookDispatcherInterface $hookDispatcher,
        CronSchedulerInterface $cronScheduler,
        TransientStoreInterface $transientStore,
    ) {
        $this->repository = $repository;
        $this->processor = $processor;
        $this->maintenance = $maintenance;
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

    public function uninstall(): void
    {
        $this->repository->dropTable();
        $this->transientStore->delete(self::LOCK_KEY);
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('cron_schedules', [$this, 'registerCronSchedule']);
        $this->hookDispatcher->addAction('init', [$this, 'ensureCronScheduled']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this->processor, 'processAllGroups']);
        $this->hookDispatcher->addAction(self::RESCUE_HOOK, [$this->maintenance, 'rescueStuckJobs']);
        $this->hookDispatcher->addAction(self::CLEANUP_HOOK, [$this->maintenance, 'cleanupJobs']);
    }

    /**
     * @param array<string, array{interval: int, display: string}> $schedules
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

    public function ensureCronScheduled(): void
    {
        $this->scheduleCronEvents();
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
}
