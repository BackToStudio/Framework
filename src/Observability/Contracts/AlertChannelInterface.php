<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for an alert delivery channel.
 *
 * Implementations deliver alerts through specific transports:
 * email, error_log, webhook, etc.
 */
interface AlertChannelInterface
{
    /**
     * Unique name of this channel (e.g., 'email', 'log', 'webhook').
     */
    public function getName(): string;

    /**
     * Send an alert through this channel.
     *
     * @param string $level    Alert severity: critical, warning, info
     * @param string $message  Human-readable alert message
     * @param array<string, mixed> $context  Additional context data
     */
    public function send(string $level, string $message, array $context = []): bool;
}
