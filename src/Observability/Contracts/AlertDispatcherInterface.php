<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for dispatching alerts across multiple channels.
 */
interface AlertDispatcherInterface
{
    /**
     * Dispatch a critical alert to all channels.
     *
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Dispatch a warning alert to all channels.
     *
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Dispatch an informational alert to all channels.
     *
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Dispatch an alert at the given level.
     *
     * @param string $level   One of: critical, warning, info
     * @param array<string, mixed> $context
     */
    public function dispatch(string $level, string $message, array $context = []): void;

    /**
     * Register an alert channel.
     */
    public function addChannel(AlertChannelInterface $channel): void;
}
