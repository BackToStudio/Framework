<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueDispatcher;
use BackTo\Framework\Queue\QueueRegistry;
use PHPUnit\Framework\TestCase;

class QueueDispatcherTest extends TestCase
{
    private QueueRepositoryInterface $repository;
    private QueueRegistry $registry;
    private JobFactory $factory;
    private QueueDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueRepositoryInterface::class);
        $this->registry = new QueueRegistry();
        $this->factory = new JobFactory();

        $this->dispatcher = new QueueDispatcher(
            $this->repository,
            $this->registry,
            $this->factory
        );
    }

    public function testDispatchEnqueuesJobAndReturnsId(): void
    {
        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job): bool {
                return $job->getKey() === 'send_email'
                    && $job->getPayload() === ['to' => 'user@example.com']
                    && $job->getGroup() === 'emails';
            }))
            ->willReturn(42);

        $id = $this->dispatcher->dispatch('send_email', ['to' => 'user@example.com'], 0, 'emails');

        $this->assertSame(42, $id);
    }

    public function testDispatchUsesHandlerMaxRetriesWhenRegistered(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('send_email');
        $handler->method('getLabel')->willReturn('Send Email');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(7);

        $this->registry->add($handler);

        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job): bool {
                return $job->getMaxRetries() === 7;
            }))
            ->willReturn(1);

        $this->dispatcher->dispatch('send_email');
    }

    public function testDispatchUsesDefaultMaxRetriesWhenNoHandler(): void
    {
        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job): bool {
                return $job->getMaxRetries() === 3;
            }))
            ->willReturn(1);

        $this->dispatcher->dispatch('unregistered_job');
    }

    public function testDispatchWithDelay(): void
    {
        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) use ($before): bool {
                $expected = $before->modify('+60 seconds');

                return $job->getScheduledAt() !== null
                    && $job->getScheduledAt() >= $expected;
            }))
            ->willReturn(1);

        $this->dispatcher->dispatch('send_email', [], 60);
    }

    public function testScheduleCreatesRecurringJob(): void
    {
        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job): bool {
                return $job->getKey() === 'cleanup_logs'
                    && $job->getIntervalSeconds() === 3600
                    && $job->isRecurring();
            }))
            ->willReturn(10);

        $id = $this->dispatcher->schedule('cleanup_logs', 3600);

        $this->assertSame(10, $id);
    }

    public function testScheduleUsesHandlerMaxRetries(): void
    {
        $handler = $this->createMock(JobInterface::class);
        $handler->method('getKey')->willReturn('sync_data');
        $handler->method('getLabel')->willReturn('Sync Data');
        $handler->method('getGroup')->willReturn('default');
        $handler->method('getMaxRetries')->willReturn(10);

        $this->registry->add($handler);

        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job): bool {
                return $job->getMaxRetries() === 10;
            }))
            ->willReturn(1);

        $this->dispatcher->schedule('sync_data', 600);
    }

    public function testScheduleWithPayloadAndGroup(): void
    {
        $payload = ['source' => 'api', 'limit' => 100];

        $this->repository->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) use ($payload): bool {
                return $job->getPayload() === $payload
                    && $job->getGroup() === 'sync';
            }))
            ->willReturn(5);

        $this->dispatcher->schedule('import_data', 1800, $payload, 'sync');
    }
}
