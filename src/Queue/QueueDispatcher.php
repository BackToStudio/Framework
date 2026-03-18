<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Queue\Contracts\QueueDispatcherInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Factory\JobFactory;

final class QueueDispatcher implements QueueDispatcherInterface
{
    private readonly QueueRepositoryInterface $repository;
    private readonly QueueRegistry $registry;
    private readonly JobFactory $factory;

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
        $payloadJson = \json_encode($payload, \JSON_THROW_ON_ERROR);
        $payloadHash = \md5($payloadJson);

        if ($this->repository->hasPendingDuplicate($jobKey, $payloadHash)) {
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
