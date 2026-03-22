<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Alert;

use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\AlertChannelInterface;

/**
 * Alert channel that writes alerts to the PHP error log via the framework logger.
 */
final class LogAlertChannel implements AlertChannelInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'log';
    }

    public function send(string $level, string $message, array $context = []): bool
    {
        $formatted = \sprintf('[ALERT:%s] %s', \strtoupper($level), $message);

        match ($level) {
            'critical' => $this->logger->critical($formatted, $context),
            'warning' => $this->logger->warning($formatted, $context),
            default => $this->logger->info($formatted, $context),
        };

        return true;
    }
}
