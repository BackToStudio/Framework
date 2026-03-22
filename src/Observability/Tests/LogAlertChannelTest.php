<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Alert\LogAlertChannel;
use PHPUnit\Framework\TestCase;

final class LogAlertChannelTest extends TestCase
{
    public function test_name_is_log(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $channel = new LogAlertChannel($logger);

        $this->assertSame('log', $channel->getName());
    }

    public function test_critical_uses_logger_critical(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('[ALERT:CRITICAL] Something broke'), ['key' => 'val']);

        $channel = new LogAlertChannel($logger);
        $result = $channel->send('critical', 'Something broke', ['key' => 'val']);

        $this->assertTrue($result);
    }

    public function test_warning_uses_logger_warning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('[ALERT:WARNING]'));

        $channel = new LogAlertChannel($logger);
        $channel->send('warning', 'Low disk');
    }

    public function test_info_uses_logger_info(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('[ALERT:INFO]'));

        $channel = new LogAlertChannel($logger);
        $channel->send('info', 'All good');
    }
}
