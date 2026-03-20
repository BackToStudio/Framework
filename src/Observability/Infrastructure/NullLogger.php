<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\Contracts\LoggerInterface;

/**
 * No-op logger for production or testing.
 */
final class NullLogger implements LoggerInterface
{
    /** @param array<string, mixed> $context */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function alert(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function critical(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function error(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function warning(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function notice(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function info(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function debug(string|\Stringable $message, array $context = []): void
    {
    }

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
    }
}
