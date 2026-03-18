<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueJobStorageInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use PHPUnit\Framework\TestCase;

class QueueWorkerTest extends TestCase
{
    private QueueJobStorageInterface $repository;
    private QueueRegistry $registry;
    private QueueWorker $worker;
    private JobFactory $factory;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueJobStorageInterface::class);
        $this->registry = new QueueRegistry();
        $this->factory = new JobFactory();

        $this->worker = new QueueWorker(
            $this->repository,
            $this->registry,
            $this->factory
        );
    }

    public function testProcessQueueReturnsZeroWhenEmpty(): void
    {
        $this->repository->method('claimNextPending')->willReturn(null);

        $processed = $this->worker->processQueue();

        $this->assertSame(0, $processed);
    }

    public function testProcessQueueExecutesHandler(): void
    {
        $handled = false;

        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('test_job');
        $handler->method('getLabel')->willReturn('Test Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);
        $handler->method('handle')->willReturnCallback(function () use (&$handled) {
            $handled = true;
        });

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)->setKey('test_job')->setStatus(JobStatus::Running);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markCompleted')->with(1);

        $processed = $this->worker->processQueue();

        $this->assertSame(1, $processed);
        $this->assertTrue($handled);
    }

    public function testProcessQueueHandlesFailure(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('failing_job');
        $handler->method('getLabel')->willReturn('Failing Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);
        $handler->method('handle')->willThrowException(new \RuntimeException('Something went wrong'));

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)->setKey('failing_job')->setStatus(JobStatus::Running)->setMaxRetries(3)->setAttempts(0);

        $retryJob = new Job();
        $retryJob->setId(1)->setKey('failing_job')->setStatus(JobStatus::Failed)->setMaxRetries(3)->setAttempts(1);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markFailed')->with(1, 'Something went wrong');
        $this->repository->method('find')->with(1)->willReturn($retryJob);
        $this->repository->expects($this->once())->method('release')->with(1);

        $this->worker->processQueue();
    }

    public function testProcessQueueNoRetryWhenMaxReached(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('failing_job');
        $handler->method('getLabel')->willReturn('Failing Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);
        $handler->method('handle')->willThrowException(new \RuntimeException('Final failure'));

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)->setKey('failing_job')->setStatus(JobStatus::Running)->setMaxRetries(3)->setAttempts(2);

        $exhaustedJob = new Job();
        $exhaustedJob->setId(1)->setKey('failing_job')->setStatus(JobStatus::Failed)->setMaxRetries(3)->setAttempts(3);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->method('find')->with(1)->willReturn($exhaustedJob);
        $this->repository->expects($this->never())->method('release');

        $this->worker->processQueue();
    }

    public function testProcessQueueMarksFailedWhenNoHandler(): void
    {
        $job = new Job();
        $job->setId(1)->setKey('unknown_job')->setStatus(JobStatus::Running);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markFailed')
            ->with(1, 'No handler registered for job key: unknown_job');

        $this->worker->processQueue();
    }

    public function testProcessQueueRespectsBatchSize(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('batch_job');
        $handler->method('getLabel')->willReturn('Batch Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $jobs = [];
        for ($i = 1; $i <= 5; $i++) {
            $job = new Job();
            $job->setId($i)->setKey('batch_job')->setStatus(JobStatus::Running);
            $jobs[] = $job;
        }
        $jobs[] = null;

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...$jobs);

        $processed = $this->worker->processQueue('default', 3);

        $this->assertSame(3, $processed);
    }

    public function testProcessQueueClampsExcessiveBatchSize(): void
    {
        $this->repository->method('claimNextPending')->willReturn(null);

        // Should not crash or loop 10000 times — capped at 100.
        $processed = $this->worker->processQueue('default', 10000);

        $this->assertSame(0, $processed);
    }

    public function testProcessQueueClampsZeroBatchSize(): void
    {
        $this->repository->method('claimNextPending')->willReturn(null);

        // Zero should be clamped to 1.
        $processed = $this->worker->processQueue('default', 0);

        $this->assertSame(0, $processed);
    }

    public function testProcessQueueReschedulesRecurringJob(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('recurring_job');
        $handler->method('getLabel')->willReturn('Recurring Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)
            ->setKey('recurring_job')
            ->setGroup('sync')
            ->setPayload(['source' => 'api'])
            ->setStatus(JobStatus::Running)
            ->setMaxRetries(3)
            ->setIntervalSeconds(3600);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markCompleted')->with(1);

        // Should enqueue a new job for the next recurrence.
        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $newJob): bool {
                return $newJob->getKey() === 'recurring_job'
                    && $newJob->getGroup() === 'sync'
                    && $newJob->getPayload() === ['source' => 'api']
                    && $newJob->getIntervalSeconds() === 3600
                    && $newJob->isRecurring();
            }));

        $this->worker->processQueue();
    }

    public function testProcessQueueDoesNotRescheduleNonRecurringJob(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('one_off_job');
        $handler->method('getLabel')->willReturn('One Off Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)
            ->setKey('one_off_job')
            ->setStatus(JobStatus::Running)
            ->setIntervalSeconds(0);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markCompleted')->with(1);
        $this->repository->expects($this->never())->method('enqueue');

        $this->worker->processQueue();
    }

    public function testProcessQueueDoesNotRescheduleFailedRecurringJob(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('recurring_fail');
        $handler->method('getLabel')->willReturn('Recurring Fail');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(1);
        $handler->method('handle')->willThrowException(new \RuntimeException('Crash'));

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)
            ->setKey('recurring_fail')
            ->setStatus(JobStatus::Running)
            ->setMaxRetries(1)
            ->setAttempts(0)
            ->setIntervalSeconds(600);

        $exhaustedJob = new Job();
        $exhaustedJob->setId(1)
            ->setKey('recurring_fail')
            ->setStatus(JobStatus::Failed)
            ->setMaxRetries(1)
            ->setAttempts(1)
            ->setIntervalSeconds(600);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->method('find')->with(1)->willReturn($exhaustedJob);

        // Should not enqueue a rescheduled job since it failed.
        $this->repository->expects($this->never())->method('enqueue');
        $this->repository->expects($this->once())->method('markFailed');

        $this->worker->processQueue();
    }

    public function testProcessQueuePassesPayloadToHandler(): void
    {
        $receivedPayload = null;

        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('payload_job');
        $handler->method('getLabel')->willReturn('Payload Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);
        $handler->method('handle')->willReturnCallback(function (array $payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });

        $this->registry->add($handler);

        $expectedPayload = ['email' => 'test@example.com', 'template' => 'welcome'];

        $job = new Job();
        $job->setId(1)->setKey('payload_job')->setStatus(JobStatus::Running)->setPayload($expectedPayload);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->worker->processQueue();

        $this->assertSame($expectedPayload, $receivedPayload);
    }

    public function testProcessQueueWithSpecificGroup(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('group_job');
        $handler->method('getLabel')->willReturn('Group Job');
        $handler->method('getGroup')->willReturn('emails');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $this->repository->expects($this->once())
            ->method('claimNextPending')
            ->with('emails')
            ->willReturn(null);

        $this->worker->processQueue('emails');
    }

    public function testProcessQueueHandlesFailureWhenFindReturnsNull(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('vanishing_job');
        $handler->method('getLabel')->willReturn('Vanishing Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);
        $handler->method('handle')->willThrowException(new \RuntimeException('Error'));

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(99)->setKey('vanishing_job')->setStatus(JobStatus::Running);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markFailed');
        // find returns null (job deleted between markFailed and find).
        $this->repository->method('find')->with(99)->willReturn(null);
        // Should not call release since we can't find the job.
        $this->repository->expects($this->never())->method('release');

        $this->worker->processQueue();
    }

    public function testRecurringJobIsRescheduledBeforeMarkingComplete(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('recurring_job');
        $handler->method('getLabel')->willReturn('Recurring Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)
            ->setKey('recurring_job')
            ->setGroup('default')
            ->setStatus(JobStatus::Running)
            ->setMaxRetries(3)
            ->setIntervalSeconds(600);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        // Verify enqueue (reschedule) is called BEFORE markCompleted
        // by making enqueue fail and checking markCompleted is NOT called.
        $this->repository->expects($this->once())
            ->method('enqueue')
            ->willThrowException(new \RuntimeException('DB write failed'));

        $this->repository->expects($this->never())->method('markCompleted');

        $this->expectException(\RuntimeException::class);
        $this->worker->processQueue();
    }

    public function testNonRecurringJobCompletesNormally(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('simple_job');
        $handler->method('getLabel')->willReturn('Simple Job');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(3);

        $this->registry->add($handler);

        $job = new Job();
        $job->setId(1)->setKey('simple_job')->setStatus(JobStatus::Running)->setIntervalSeconds(0);

        $this->repository->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($job, null);

        $this->repository->expects($this->once())->method('markCompleted')->with(1);
        $this->repository->expects($this->never())->method('enqueue');

        $this->worker->processQueue();
    }
}
