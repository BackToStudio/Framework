<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\Contracts\LoggerInterface;

/**
 * WordPress adapter for PSR-3 compatible logging.
 *
 * Writes to PHP error_log with structured formatting.
 * Respects configured minimum log level.
 */
class WordPressLogger implements LoggerInterface
{
    private const LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    private string $minLevel;
    private string $channel;

    public function __construct(string $minLevel = 'debug', string $channel = 'BackTo Framework')
    {
        $this->minLevel = $minLevel;
        $this->channel = $channel;
    }

    /** @param array<string, mixed> $context */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string $message, array $context = []): void
    {
        $levelStr = (string) $level;

        if (!$this->shouldLog($levelStr)) {
            return;
        }

        $interpolated = $this->interpolate($message, $context);
        $formatted = \sprintf('[%s] %s.%s: %s', \gmdate('Y-m-d H:i:s'), $this->channel, \strtoupper($levelStr), $interpolated);

        if ($context !== [] && !$this->allContextKeysUsedInMessage($message, $context)) {
            $formatted .= ' ' . \json_encode($context, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        }

        \error_log($formatted);
    }

    private function shouldLog(string $level): bool
    {
        $levelPriority = self::LEVELS[$level] ?? 7;
        $minPriority = self::LEVELS[$this->minLevel] ?? 7;

        return $levelPriority <= $minPriority;
    }

    /**
     * PSR-3 message interpolation.
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (\is_string($val) || (\is_object($val) && \method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }

        return \strtr($message, $replace);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function allContextKeysUsedInMessage(string $message, array $context): bool
    {
        foreach (\array_keys($context) as $key) {
            if (!\str_contains($message, '{' . $key . '}')) {
                return false;
            }
        }

        return true;
    }
}
