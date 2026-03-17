<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Registry for collecting job handler definitions.
 */
interface QueueRegistryInterface
{
    public function add(JobInterface $job): self;

    /**
     * @return JobInterface[]
     */
    public function getJobs(): array;

    /**
     * Find a job handler by its key.
     */
    public function get(string $key): ?JobInterface;
}
