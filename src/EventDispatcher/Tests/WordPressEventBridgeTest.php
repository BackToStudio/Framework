<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;
use BackTo\Framework\EventDispatcher\Infrastructure\WordPressEventBridge;
use PHPUnit\Framework\TestCase;

class SomeEvent
{
    public string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }
}

class CamelCaseEvent
{
}

/**
 * @covers \BackTo\Framework\EventDispatcher\Infrastructure\WordPressEventBridge
 */
class WordPressEventBridgeTest extends TestCase
{
    public function testDispatchDelegatesToInnerAndFiresWordPressHook(): void
    {
        $event = new SomeEvent('hello');

        $inner = $this->createMock(EventDispatcherInterface::class);
        $inner->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn($event);

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->once())
            ->method('doAction')
            ->with('backto.event.some_event', $event);

        $bridge = new WordPressEventBridge($inner, $hookDispatcher);
        $result = $bridge->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testReturnsEventFromInnerDispatcher(): void
    {
        $event = new SomeEvent('test');

        $inner = $this->createMock(EventDispatcherInterface::class);
        $inner->method('dispatch')->willReturn($event);

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);

        $bridge = new WordPressEventBridge($inner, $hookDispatcher);
        $result = $bridge->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testCamelCaseClassNameConvertsToSnakeCase(): void
    {
        $event = new CamelCaseEvent();

        $inner = $this->createMock(EventDispatcherInterface::class);
        $inner->method('dispatch')->willReturn($event);

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->once())
            ->method('doAction')
            ->with('backto.event.camel_case_event', $event);

        $bridge = new WordPressEventBridge($inner, $hookDispatcher);
        $bridge->dispatch($event);
    }

    public function testHookNameIsCachedAcrossDispatches(): void
    {
        $inner = $this->createMock(EventDispatcherInterface::class);
        $inner->method('dispatch')->willReturnArgument(0);

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->exactly(2))
            ->method('doAction')
            ->with('backto.event.some_event', $this->isInstanceOf(SomeEvent::class));

        $bridge = new WordPressEventBridge($inner, $hookDispatcher);
        $bridge->dispatch(new SomeEvent('first'));
        $bridge->dispatch(new SomeEvent('second'));
    }
}
