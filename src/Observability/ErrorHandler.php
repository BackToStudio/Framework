<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Observability\Contracts\ErrorHandlerInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;

/**
 * Framework error handler with error boundary support.
 *
 * In debug mode, exceptions are re-thrown for immediate visibility.
 * In production, exceptions are logged and execution continues gracefully.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    private LoggerInterface $logger;
    private bool $debug;

    public function __construct(LoggerInterface $logger, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /** @param array<string, mixed> $context */
    public function handle(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = \get_class($exception);
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();

        if ($exception->getPrevious() !== null) {
            $context['previous'] = \get_class($exception->getPrevious());
        }

        $this->logger->error($exception->getMessage(), $context);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @param T $fallback
     * @return T
     */
    public function capture(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            if ($this->debug) {
                throw $e;
            }

            $this->handle($e);

            return $fallback;
        }
    }
}
