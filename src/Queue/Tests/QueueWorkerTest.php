<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use PHPUnit\Framework\TestCase;

class QueueWorkerTest extends TestCase
{
    private QueueRepositoryInterface $repository;
    private QueueRegistry $registry;
    private QueueWorker $worker;
    private JobFactory $factory;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueRepositoryInterface::class);
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
}
