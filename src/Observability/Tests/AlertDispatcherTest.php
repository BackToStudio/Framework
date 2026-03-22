<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Alert\AlertDispatcher;
use BackTo\Framework\Observability\Contracts\AlertChannelInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class AlertDispatcherTest extends TestCase
{
    private AlertDispatcher $dispatcher;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = new AlertDispatcher($this->logger);
    }

    public function test_dispatches_to_all_channels(): void
    {
        $channel1 = $this->createMock(AlertChannelInterface::class);
        $channel1->expects($this->once())
            ->method('send')
            ->with('critical', 'Test alert', ['key' => 'value']);

        $channel2 = $this->createMock(AlertChannelInterface::class);
        $channel2->expects($this->once())
            ->method('send')
            ->with('critical', 'Test alert', ['key' => 'value']);

        $this->dispatcher->addChannel($channel1);
        $this->dispatcher->addChannel($channel2);

        $this->dispatcher->critical('Test alert', ['key' => 'value']);
    }

    public function test_logs_warning_when_no_channels(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('no channels registered'));

        $this->dispatcher->dispatch('warning', 'Test');
    }

    public function test_continues_on_channel_failure(): void
    {
        $failingChannel = $this->createMock(AlertChannelInterface::class);
        $failingChannel->method('getName')->willReturn('failing');
        $failingChannel->method('send')->willThrowException(new \RuntimeException('fail'));

        $workingChannel = $this->createMock(AlertChannelInterface::class);
        $workingChannel->expects($this->once())
            ->method('send')
            ->with('critical', 'Test')
            ->willReturn(true);

        $this->dispatcher->addChannel($failingChannel);
        $this->dispatcher->addChannel($workingChannel);

        $this->dispatcher->critical('Test');
    }

    public function test_critical_dispatches_with_correct_level(): void
    {
        $channel = $this->createMock(AlertChannelInterface::class);
        $channel->expects($this->once())
            ->method('send')
            ->with('critical', 'msg', []);

        $this->dispatcher->addChannel($channel);
        $this->dispatcher->critical('msg');
    }

    public function test_warning_dispatches_with_correct_level(): void
    {
        $channel = $this->createMock(AlertChannelInterface::class);
        $channel->expects($this->once())
            ->method('send')
            ->with('warning', 'msg', []);

        $this->dispatcher->addChannel($channel);
        $this->dispatcher->warning('msg');
    }

    public function test_info_dispatches_with_correct_level(): void
    {
        $channel = $this->createMock(AlertChannelInterface::class);
        $channel->expects($this->once())
            ->method('send')
            ->with('info', 'msg', []);

        $this->dispatcher->addChannel($channel);
        $this->dispatcher->info('msg');
    }

    public function test_get_channels_returns_registered_channels(): void
    {
        $channel = $this->createMock(AlertChannelInterface::class);
        $this->dispatcher->addChannel($channel);

        $this->assertCount(1, $this->dispatcher->getChannels());
        $this->assertSame($channel, $this->dispatcher->getChannels()[0]);
    }
}
