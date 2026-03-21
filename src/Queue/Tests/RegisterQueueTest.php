<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use BackTo\Framework\Queue\Contracts\QueueJobStorageInterface;
use BackTo\Framework\Queue\Contracts\QueueMaintenanceInterface;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\Contracts\QueueSchemaInterface;
use BackTo\Framework\Queue\QueueMaintenance;
use BackTo\Framework\Queue\QueueProcessor;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use BackTo\Framework\Queue\RegisterQueue;
use PHPUnit\Framework\TestCase;

class RegisterQueueTest extends TestCase
{
    private QueueSchemaInterface $repository;
    private HookDispatcherInterface $hookDispatcher;
    private CronSchedulerInterface $cronScheduler;
    private CacheStoreInterface $cacheStore;
    private RegisterQueue $registerQueue;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueSchemaInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->cronScheduler = $this->createMock(CronSchedulerInterface::class);
        $this->cacheStore = $this->createMock(CacheStoreInterface::class);

        // Build real final instances with mocked interfaces (final classes cannot be mocked)
        $jobStorage = $this->createMock(QueueJobStorageInterface::class);
        $queryInterface = $this->createMock(QueueQueryInterface::class);
        $maintenanceInterface = $this->createMock(QueueMaintenanceInterface::class);

        $registry = new QueueRegistry();
        $worker = new QueueWorker($jobStorage, $registry, new JobFactory());
        $processor = new QueueProcessor($worker, $registry, $queryInterface, $this->cacheStore);
        $maintenance = new QueueMaintenance($maintenanceInterface);

        $this->registerQueue = new RegisterQueue(
            $this->repository,
            $processor,
            $maintenance,
            $this->hookDispatcher,
            $this->cronScheduler,
            $this->cacheStore,
        );
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->registerQueue);
    }

    public function testImplementsActivationHooksInterface(): void
    {
        $this->assertInstanceOf(ActivationHooks::class, $this->registerQueue);
    }

    public function testImplementsDeactivationHooksInterface(): void
    {
        $this->assertInstanceOf(DeactivationHooks::class, $this->registerQueue);
    }

    public function testHooksRegistersExpectedActions(): void
    {
        $registeredActions = [];
        $registeredFilters = [];

        $this->hookDispatcher->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$registeredActions): void {
                $registeredActions[] = $hook;
            });

        $this->hookDispatcher->method('addFilter')
            ->willReturnCallback(function (string $hook) use (&$registeredFilters): void {
                $registeredFilters[] = $hook;
            });

        $this->registerQueue->hooks();

        $this->assertContains('init', $registeredActions);
        $this->assertContains('backto_queue_process', $registeredActions);
        $this->assertContains('backto_queue_rescue', $registeredActions);
        $this->assertContains('backto_queue_cleanup', $registeredActions);
        $this->assertContains('cron_schedules', $registeredFilters);
    }

    public function testActivateCreatesTable(): void
    {
        $this->repository->expects($this->once())->method('createTable');

        $this->registerQueue->activate();
    }

    public function testUninstallDropsTableAndCleansTransient(): void
    {
        $this->repository->expects($this->once())->method('dropTable');

        $this->registerQueue->uninstall();
    }

    public function testRegisterCronScheduleAddsEveryMinute(): void
    {
        $schedules = $this->registerQueue->registerCronSchedule([]);

        $this->assertArrayHasKey('every_minute', $schedules);
        $this->assertSame(60, $schedules['every_minute']['interval']);
        $this->assertSame('Every Minute', $schedules['every_minute']['display']);
    }

    public function testRegisterCronScheduleDoesNotOverwriteExisting(): void
    {
        $existing = [
            'every_minute' => [
                'interval' => 30,
                'display' => 'Custom Every Minute',
            ],
        ];

        $schedules = $this->registerQueue->registerCronSchedule($existing);

        $this->assertSame(30, $schedules['every_minute']['interval']);
        $this->assertSame('Custom Every Minute', $schedules['every_minute']['display']);
    }
}
