<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Contracts;

/**
 * Backend storage for locks.
 *
 * Port interface — implementations may use WordPress transients, Redis,
 * database advisory locks, or in-memory storage.
 */
interface LockStoreInterface
{
    /**
     * Try to acquire a lock for the given resource.
     *
     * @param string $resource The resource identifier.
     * @param string $token A unique token identifying this lock holder.
     * @param int $ttl Time-to-live in seconds (auto-release safety net).
     * @return bool True if the lock was acquired.
     */
    public function acquire(string $resource, string $token, int $ttl): bool;

    /**
     * Release a lock for the given resource.
     *
     * Must only release if the token matches (owner check).
     *
     * @param string $resource The resource identifier.
     * @param string $token The token used when acquiring.
     */
    public function release(string $resource, string $token): void;

    /**
     * Check if a lock exists for the given resource.
     */
    public function exists(string $resource): bool;
}
