<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Represents an async job that can be dispatched to a queue.
 *
 * Implement this interface to define jobs processed in the background.
 * Each job class is auto-tagged and registered in the QueueRegistry.
 */
interface JobInterface
{
    /**
     * Get the unique identifier for this job type.
     *
     * Used as the action hook name for dispatching.
     */
    public function getKey(): string;

    /**
     * Get the human-readable label for this job.
     */
    public function getLabel(): string;

    /**
     * Get the queue group this job belongs to.
     *
     * Jobs within the same group are processed sequentially.
     * Defaults to 'default'.
     */
    public function getGroup(): string;

    /**
     * Get the maximum number of retry attempts.
     */
    public function getMaxRetries(): int;

    /**
     * Handle the job execution.
     *
     * @param array<string, mixed> $payload The job payload data.
     *
     * @throws \Throwable If the job fails, it will be retried up to maxRetries.
     */
    public function handle(array $payload): void;
}
