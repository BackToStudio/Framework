<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\QueueProcessor;
use BackTo\Framework\Queue\QueueRegistry;
use BackTo\Framework\Queue\QueueWorker;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Factory\JobFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QueueProcessor lock mechanism.
 */
class QueueProcessorLockTest extends TestCase
{
    public function testLockIsExplicitlyCheckedBeforeAcquire(): void
    {
        $store = $this->createMock(TransientStoreInterface::class);

        // Simulate lock already held — get() returns a PID
        $store->method('get')->willReturn(12345);
        $store->expects($this->never())->method('set');

        $repo = $this->createMock(QueueRepositoryInterface::class);
        $registry = new QueueRegistry();
        $worker = new QueueWorker($repo, $registry, new JobFactory());

        $processor = new QueueProcessor($worker, $registry, $repo, $store);

        // Should not process anything since lock is held
        $repo->expects($this->never())->method('claimNextPending');
        $processor->processAllGroups();
    }

    public function testLockReleasedAfterProcessing(): void
    {
        $storeData = [];
        $store = $this->createMock(TransientStoreInterface::class);

        $store->method('get')
            ->willReturnCallback(fn (string $key) => $storeData[$key] ?? false);
        $store->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$storeData): bool {
                $storeData[$key] = $value;
                return true;
            });
        $store->method('delete')
            ->willReturnCallback(function (string $key) use (&$storeData): bool {
                unset($storeData[$key]);
                return true;
            });

        $repo = $this->createMock(QueueRepositoryInterface::class);
        $repo->method('getActiveGroups')->willReturn([]);
        $repo->method('claimNextPending')->willReturn(null);

        $registry = new QueueRegistry();
        $worker = new QueueWorker($repo, $registry, new JobFactory());

        $processor = new QueueProcessor($worker, $registry, $repo, $store);
        $processor->processAllGroups();

        // After processing, lock should be released
        $this->assertArrayNotHasKey('backto_queue_lock', $storeData);
    }

    public function testLockReleasedEvenOnException(): void
    {
        $storeData = [];
        $store = $this->createMock(TransientStoreInterface::class);

        $store->method('get')
            ->willReturnCallback(fn (string $key) => $storeData[$key] ?? false);
        $store->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$storeData): bool {
                $storeData[$key] = $value;
                return true;
            });
        $store->method('delete')
            ->willReturnCallback(function (string $key) use (&$storeData): bool {
                unset($storeData[$key]);
                return true;
            });

        // Make getActiveGroups throw to simulate an error during processing
        $repo = $this->createMock(QueueRepositoryInterface::class);
        $repo->method('getActiveGroups')->willThrowException(new \RuntimeException('DB error'));
        $repo->method('claimNextPending')->willReturn(null);

        $registry = new QueueRegistry();
        $worker = new QueueWorker($repo, $registry, new JobFactory());

        $processor = new QueueProcessor($worker, $registry, $repo, $store);

        try {
            $processor->processAllGroups();
        } catch (\RuntimeException) {
            // Expected
        }

        // Lock should still be released thanks to finally block
        $this->assertArrayNotHasKey('backto_queue_lock', $storeData);
    }
}
