<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Exception;

/**
 * Thrown when a network-level error occurs (connection refused, DNS failure, etc.).
 */
class NetworkException extends \RuntimeException
{
    public function __construct(string $url, string $reason = '', ?\Throwable $previous = null)
    {
        $safeUrl = self::redactUrl($url);
        $message = sprintf('Network error for "%s"', $safeUrl);
        if ($reason !== '') {
            $message .= ': ' . $reason;
        }

        parent::__construct($message, 0, $previous);
    }

    private static function redactUrl(string $url): string
    {
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['host'])) {
            return '(invalid URL)';
        }

        $safe = ($parsed['scheme'] ?? 'http') . '://' . $parsed['host'];

        if (isset($parsed['port'])) {
            $safe .= ':' . $parsed['port'];
        }

        $safe .= $parsed['path'] ?? '/';

        return $safe;
    }
}
