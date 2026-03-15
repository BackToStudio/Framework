<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * PSR-3 compatible logger interface.
 *
 * Port interface — domain and application layers depend on this contract.
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
interface LoggerInterface
{
    /** @param array<string, mixed> $context */
    public function emergency(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function alert(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function notice(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void;

    /**
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, string $message, array $context = []): void;
}
