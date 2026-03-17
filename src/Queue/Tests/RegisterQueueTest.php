<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use BackTo\Framework\Queue\RegisterQueue;
use PHPUnit\Framework\TestCase;

class RegisterQueueTest extends TestCase
{
    private QueueRepositoryInterface $repository;
    private QueueWorker $worker;
    private QueueRegistry $registry;
    private HookDispatcherInterface $hookDispatcher;
    private RegisterQueue $registerQueue;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueRepositoryInterface::class);
        $this->registry = new QueueRegistry();
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);

        $this->worker = new QueueWorker(
            $this->repository,
            $this->registry,
            new JobFactory()
        );

        $this->registerQueue = new RegisterQueue(
            $this->repository,
            $this->worker,
            $this->registry,
            $this->hookDispatcher
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

    public function testProcessAllGroupsProcessesDefaultGroup(): void
    {
        // With no registered jobs, only 'default' group should be processed.
        // Worker will call claimNextPending('default').
        $this->repository->expects($this->once())
            ->method('claimNextPending')
            ->with('default')
            ->willReturn(null);

        $this->registerQueue->processAllGroups();
    }

    public function testProcessAllGroupsProcessesMultipleGroups(): void
    {
        $emailJob = $this->createMock(JobInterface::class);
        $emailJob->method('getKey')->willReturn('send_email');
        $emailJob->method('getLabel')->willReturn('Send Email');
        $emailJob->method('getGroup')->willReturn('emails');
        $emailJob->method('getMaxRetries')->willReturn(3);

        $mediaJob = $this->createMock(JobInterface::class);
        $mediaJob->method('getKey')->willReturn('process_image');
        $mediaJob->method('getLabel')->willReturn('Process Image');
        $mediaJob->method('getGroup')->willReturn('media');
        $mediaJob->method('getMaxRetries')->willReturn(5);

        $this->registry->add($emailJob);
        $this->registry->add($mediaJob);

        $processedGroups = [];

        $this->repository->method('claimNextPending')
            ->willReturnCallback(function (string $group) use (&$processedGroups) {
                $processedGroups[] = $group;

                return null;
            });

        $this->registerQueue->processAllGroups();

        $this->assertContains('default', $processedGroups);
        $this->assertContains('emails', $processedGroups);
        $this->assertContains('media', $processedGroups);
        $this->assertCount(3, $processedGroups);
    }

    public function testRescueStuckJobsDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('rescueStuck')
            ->with(300);

        $this->registerQueue->rescueStuckJobs();
    }

    public function testCleanupCompletedJobsDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('cleanup')
            ->with(86400);

        $this->registerQueue->cleanupCompletedJobs();
    }

    public function testProcessAllGroupsDeduplicatesGroups(): void
    {
        $job1 = $this->createMock(JobInterface::class);
        $job1->method('getKey')->willReturn('job_a');
        $job1->method('getLabel')->willReturn('Job A');
        $job1->method('getGroup')->willReturn('default');
        $job1->method('getMaxRetries')->willReturn(3);

        $job2 = $this->createMock(JobInterface::class);
        $job2->method('getKey')->willReturn('job_b');
        $job2->method('getLabel')->willReturn('Job B');
        $job2->method('getGroup')->willReturn('default');
        $job2->method('getMaxRetries')->willReturn(3);

        $this->registry->add($job1);
        $this->registry->add($job2);

        $processedGroups = [];

        $this->repository->method('claimNextPending')
            ->willReturnCallback(function (string $group) use (&$processedGroups) {
                $processedGroups[] = $group;

                return null;
            });

        $this->registerQueue->processAllGroups();

        // Should only process 'default' once, not twice.
        $this->assertCount(1, $processedGroups);
        $this->assertSame(['default'], $processedGroups);
    }
}
