<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Tests;

use BackTo\Framework\EventDispatcher\Contracts\EventSubscriberInterface;
use BackTo\Framework\EventDispatcher\Contracts\StoppableEventInterface;
use BackTo\Framework\EventDispatcher\Event;
use BackTo\Framework\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;

class UserRegistered
{
    public string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}

class OrderPlaced
{
    public int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }
}

class StoppableEvent extends Event
{
}

class TestSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    public array $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => 'onUserRegistered',
            OrderPlaced::class => ['onOrderPlaced', 10],
        ];
    }

    public function onUserRegistered(UserRegistered $event): void
    {
        $this->calls[] = 'onUserRegistered:' . $event->email;
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->calls[] = 'onOrderPlaced:' . $event->orderId;
    }
}

class MultiListenerSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    public array $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => [
                ['onUserRegisteredFirst', 10],
                ['onUserRegisteredSecond', -10],
            ],
        ];
    }

    public function onUserRegisteredFirst(UserRegistered $event): void
    {
        $this->calls[] = 'first:' . $event->email;
    }

    public function onUserRegisteredSecond(UserRegistered $event): void
    {
        $this->calls[] = 'second:' . $event->email;
    }
}

/**
 * @covers \BackTo\Framework\EventDispatcher\EventDispatcher
 * @covers \BackTo\Framework\EventDispatcher\Event
 */
class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testDispatchReturnsTheEvent(): void
    {
        $event = new UserRegistered('test@example.com');
        $result = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testDispatchWithNoListenersDoesNothing(): void
    {
        $event = new UserRegistered('test@example.com');
        $this->dispatcher->dispatch($event);

        $this->assertSame('test@example.com', $event->email);
    }

    public function testAddListenerAndDispatch(): void
    {
        $called = false;
        $this->dispatcher->addListener(UserRegistered::class, function (UserRegistered $e) use (&$called): void {
            $called = true;
        });

        $this->dispatcher->dispatch(new UserRegistered('test@example.com'));

        $this->assertTrue($called);
    }

    public function testListenersCalledInPriorityOrder(): void
    {
        $order = [];

        $this->dispatcher->addListener(UserRegistered::class, function () use (&$order): void {
            $order[] = 'low';
        }, -10);

        $this->dispatcher->addListener(UserRegistered::class, function () use (&$order): void {
            $order[] = 'high';
        }, 10);

        $this->dispatcher->addListener(UserRegistered::class, function () use (&$order): void {
            $order[] = 'default';
        }, 0);

        $this->dispatcher->dispatch(new UserRegistered('test@example.com'));

        $this->assertSame(['high', 'default', 'low'], $order);
    }

    public function testStoppableEventStopsPropagation(): void
    {
        $order = [];

        $this->dispatcher->addListener(StoppableEvent::class, function (StoppableEvent $e) use (&$order): void {
            $order[] = 'first';
            $e->stopPropagation();
        }, 10);

        $this->dispatcher->addListener(StoppableEvent::class, function () use (&$order): void {
            $order[] = 'second';
        }, 0);

        $this->dispatcher->dispatch(new StoppableEvent());

        $this->assertSame(['first'], $order);
    }

    public function testAlreadyStoppedEventSkipsAllListeners(): void
    {
        $called = false;

        $this->dispatcher->addListener(StoppableEvent::class, function () use (&$called): void {
            $called = true;
        });

        $event = new StoppableEvent();
        $event->stopPropagation();
        $this->dispatcher->dispatch($event);

        $this->assertFalse($called);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners(UserRegistered::class));

        $this->dispatcher->addListener(UserRegistered::class, function (): void {});

        $this->assertTrue($this->dispatcher->hasListeners(UserRegistered::class));
    }

    public function testRemoveListener(): void
    {
        $listener = function (): void {};

        $this->dispatcher->addListener(UserRegistered::class, $listener);
        $this->assertTrue($this->dispatcher->hasListeners(UserRegistered::class));

        $this->dispatcher->removeListener(UserRegistered::class, $listener);
        $this->assertFalse($this->dispatcher->hasListeners(UserRegistered::class));
    }

    public function testRemoveListenerForUnknownEventDoesNothing(): void
    {
        $this->dispatcher->removeListener(UserRegistered::class, function (): void {});

        $this->assertFalse($this->dispatcher->hasListeners(UserRegistered::class));
    }

    public function testAddSubscriberWithStringMethod(): void
    {
        $subscriber = new TestSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch(new UserRegistered('alice@example.com'));

        $this->assertSame(['onUserRegistered:alice@example.com'], $subscriber->calls);
    }

    public function testAddSubscriberWithMethodAndPriority(): void
    {
        $subscriber = new TestSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch(new OrderPlaced(42));

        $this->assertSame(['onOrderPlaced:42'], $subscriber->calls);
    }

    public function testAddSubscriberWithMultipleListeners(): void
    {
        $subscriber = new MultiListenerSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch(new UserRegistered('bob@example.com'));

        $this->assertSame(['first:bob@example.com', 'second:bob@example.com'], $subscriber->calls);
    }

    public function testAddListenerThrowsOnEmptyEventClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class name must not be empty');

        $this->dispatcher->addListener('', function (): void {});
    }

    public function testEventBaseClassImplementsStoppableInterface(): void
    {
        $event = new Event();

        $this->assertInstanceOf(StoppableEventInterface::class, $event);
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDifferentEventTypesDoNotInterfere(): void
    {
        $userCalled = false;
        $orderCalled = false;

        $this->dispatcher->addListener(UserRegistered::class, function () use (&$userCalled): void {
            $userCalled = true;
        });

        $this->dispatcher->addListener(OrderPlaced::class, function () use (&$orderCalled): void {
            $orderCalled = true;
        });

        $this->dispatcher->dispatch(new UserRegistered('test@example.com'));

        $this->assertTrue($userCalled);
        $this->assertFalse($orderCalled);
    }
}
