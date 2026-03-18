<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\QueueMaintenance;
use PHPUnit\Framework\TestCase;

class QueueMaintenanceTest extends TestCase
{
    private QueueRepositoryInterface $repository;
    private QueueMaintenance $maintenance;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(QueueRepositoryInterface::class);
        $this->maintenance = new QueueMaintenance($this->repository);
    }

    public function testRescueStuckJobsDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('rescueStuck')
            ->with(300);

        $this->maintenance->rescueStuckJobs();
    }

    public function testCleanupJobsDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('cleanup')
            ->with(86400);

        $this->repository->expects($this->once())
            ->method('cleanupFailed')
            ->with(604800);

        $this->maintenance->cleanupJobs();
    }
}
