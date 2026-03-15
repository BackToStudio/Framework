<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for framework error handling.
 *
 * Provides error boundaries: catches exceptions from hook execution
 * and delegates to the logger instead of crashing the entire WordPress request.
 */
interface ErrorHandlerInterface
{
    /**
     * Handle an exception caught during framework execution.
     *
     * @param array<string, mixed> $context
     */
    public function handle(\Throwable $exception, array $context = []): void;

    /**
     * Execute a callback within an error boundary.
     *
     * Returns the callback result on success, or the fallback value on failure.
     *
     * @template T
     * @param callable(): T $callback
     * @param T $fallback
     * @return T
     */
    public function capture(callable $callback, mixed $fallback = null): mixed;
}
