<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\QueueProcessor;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use PHPUnit\Framework\TestCase;

class QueueProcessorTest extends TestCase
{
    private QueueRepositoryInterface $repository;
    private QueueRegistry $registry;
    private CacheStoreInterface $transientStore;
    private QueueProcessor $processor;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueRepositoryInterface::class);
        $this->registry = new QueueRegistry();
        $this->transientStore = $this->createMock(CacheStoreInterface::class);

        $worker = new QueueWorker(
            $this->repository,
            $this->registry,
            new JobFactory()
        );

        $this->processor = new QueueProcessor(
            $worker,
            $this->registry,
            $this->repository,
            $this->transientStore,
        );
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

        $this->repository->method('getActiveGroups')->willReturn([]);

        $processedGroups = [];
        $this->repository->method('claimNextPending')
            ->willReturnCallback(function (string $group) use (&$processedGroups) {
                $processedGroups[] = $group;
                return null;
            });

        $this->processor->processAllGroups();

        $this->assertCount(1, $processedGroups);
        $this->assertSame(['default'], $processedGroups);
    }

    public function testProcessAllGroupsMergesDbGroups(): void
    {
        $emailJob = $this->createMock(JobInterface::class);
        $emailJob->method('getKey')->willReturn('send_email');
        $emailJob->method('getLabel')->willReturn('Send Email');
        $emailJob->method('getGroup')->willReturn('emails');
        $emailJob->method('getMaxRetries')->willReturn(3);

        $this->registry->add($emailJob);

        $this->repository->method('getActiveGroups')->willReturn(['emails', 'orphan_group']);

        $processedGroups = [];
        $this->repository->method('claimNextPending')
            ->willReturnCallback(function (string $group) use (&$processedGroups) {
                $processedGroups[] = $group;
                return null;
            });

        $this->processor->processAllGroups();

        $this->assertContains('default', $processedGroups);
        $this->assertContains('emails', $processedGroups);
        $this->assertContains('orphan_group', $processedGroups);
        $this->assertCount(3, $processedGroups);
    }

    public function testProcessAllGroupsSkipsWhenLocked(): void
    {
        $this->transientStore->method('get')->willReturn('1');

        $this->repository->expects($this->never())->method('claimNextPending');

        $this->processor->processAllGroups();
    }
}
