<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Contracts;

/**
 * Creates locks for named resources.
 *
 * Port interface — inject this to create locks without coupling
 * to a specific store implementation.
 */
interface LockFactoryInterface
{
    /**
     * Create a lock for the given resource.
     *
     * @param string $resource A unique name identifying the shared resource.
     * @param int $ttl Time-to-live in seconds. The lock auto-releases after
     *                  this duration as a safety net against crashes.
     */
    public function create(string $resource, int $ttl = 300): LockInterface;
}
