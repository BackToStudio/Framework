<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Queue\Contracts\QueueDispatcherInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Factory\JobFactory;
use BackTo\Framework\Queue\Infrastructure\WordPressQueueRepository;

class QueueDispatcher implements QueueDispatcherInterface
{
    private QueueRepositoryInterface $repository;
    private QueueRegistry $registry;
    private JobFactory $factory;

    public function __construct(
        QueueRepositoryInterface $repository,
        QueueRegistry $registry,
        JobFactory $factory
    ) {
        $this->repository = $repository;
        $this->registry = $registry;
        $this->factory = $factory;
    }

    public function dispatch(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): int
    {
        $handler = $this->registry->get($jobKey);
        $maxRetries = $handler !== null ? $handler->getMaxRetries() : 3;

        $job = $this->factory->create($jobKey, $payload, $group, $maxRetries, $delay);

        return $this->repository->enqueue($job);
    }

    public function dispatchUnique(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): ?int
    {
        $payloadJson = \wp_json_encode($payload) ?: '[]';

        if ($this->repository instanceof WordPressQueueRepository && $this->repository->hasPendingDuplicate($jobKey, $payloadJson)) {
            return null;
        }

        return $this->dispatch($jobKey, $payload, $delay, $group);
    }

    public function schedule(string $jobKey, int $intervalSeconds, array $payload = [], string $group = 'default'): int
    {
        $handler = $this->registry->get($jobKey);
        $maxRetries = $handler !== null ? $handler->getMaxRetries() : 3;

        $job = $this->factory->create($jobKey, $payload, $group, $maxRetries, 0, $intervalSeconds);

        return $this->repository->enqueue($job);
    }
}
