<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Contracts;

/**
 * Represents an exclusive lock on a shared resource.
 *
 * Usage:
 *     $lock = $factory->create('invoice-generation');
 *     if ($lock->acquire()) {
 *         try {
 *             // critical section
 *         } finally {
 *             $lock->release();
 *         }
 *     }
 */
interface LockInterface
{
    /**
     * Try to acquire the lock.
     *
     * @return bool True if the lock was acquired, false if already held.
     */
    public function acquire(): bool;

    /**
     * Release the lock.
     *
     * Safe to call even if the lock was not acquired (no-op in that case).
     */
    public function release(): void;

    /**
     * Whether this lock instance currently holds the lock.
     */
    public function isAcquired(): bool;

    /**
     * The resource name this lock protects.
     */
    public function getResource(): string;
}
