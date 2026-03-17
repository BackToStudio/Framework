<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueRegistryInterface;

class QueueRegistry implements RegistryInterface, QueueRegistryInterface
{
    /** @var array<string, JobInterface> */
    private array $jobs = [];

    public function add(JobInterface $job): QueueRegistryInterface
    {
        $this->jobs[$job->getKey()] = $job;

        return $this;
    }

    /**
     * @return JobInterface[]
     */
    public function getJobs(): array
    {
        return \array_values($this->jobs);
    }

    public function get(string $key): ?JobInterface
    {
        return $this->jobs[$key] ?? null;
    }
}
