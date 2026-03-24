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
        $message = sprintf('Network error for "%s"', $url);
        if ($reason !== '') {
            $message .= ': ' . $reason;
        }

        parent::__construct($message, 0, $previous);
    }
}
