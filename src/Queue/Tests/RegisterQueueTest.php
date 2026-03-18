<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use BackTo\Framework\Queue\Contracts\QueueSchemaInterface;
use BackTo\Framework\Queue\QueueMaintenance;
use BackTo\Framework\Queue\QueueProcessor;
use BackTo\Framework\Queue\RegisterQueue;
use PHPUnit\Framework\TestCase;

class RegisterQueueTest extends TestCase
{
    private QueueSchemaInterface $repository;
    private HookDispatcherInterface $hookDispatcher;
    private CronSchedulerInterface $cronScheduler;
    private TransientStoreInterface $transientStore;
    private QueueProcessor $processor;
    private QueueMaintenance $maintenance;
    private RegisterQueue $registerQueue;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueSchemaInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->cronScheduler = $this->createMock(CronSchedulerInterface::class);
        $this->transientStore = $this->createMock(TransientStoreInterface::class);
        $this->processor = $this->createMock(QueueProcessor::class);
        $this->maintenance = $this->createMock(QueueMaintenance::class);

        $this->registerQueue = new RegisterQueue(
            $this->repository,
            $this->processor,
            $this->maintenance,
            $this->hookDispatcher,
            $this->cronScheduler,
            $this->transientStore,
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
