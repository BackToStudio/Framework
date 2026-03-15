<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\ErrorHandler;
use BackTo\Framework\Observability\Infrastructure\NullLogger;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function testCaptureReturnsCallbackResult(): void
    {
        $handler = new ErrorHandler(new NullLogger());

        $result = $handler->capture(static fn () => 42);

        $this->assertSame(42, $result);
    }

    public function testCaptureReturnsFallbackOnException(): void
    {
        $handler = new ErrorHandler(new NullLogger(), false);

        $result = $handler->capture(
            static fn () => throw new \RuntimeException('boom'),
            'fallback'
        );

        $this->assertSame('fallback', $result);
    }

    public function testCaptureRethrowsInDebugMode(): void
    {
        $handler = new ErrorHandler(new NullLogger(), true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $handler->capture(static fn () => throw new \RuntimeException('boom'));
    }

    public function testHandleLogsException(): void
    {
        $logger = $this->createMock(\BackTo\Framework\Observability\Contracts\LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'test error',
                $this->callback(static function (array $context): bool {
                    return $context['exception'] === 'RuntimeException'
                        && isset($context['file'])
                        && isset($context['line']);
                })
            );

        $handler = new ErrorHandler($logger);
        $handler->handle(new \RuntimeException('test error'));
    }

    public function testCaptureReturnsFallbackNullByDefault(): void
    {
        $handler = new ErrorHandler(new NullLogger(), false);

        $result = $handler->capture(static fn () => throw new \RuntimeException('boom'));

        $this->assertNull($result);
    }
}
