<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventSubscriberInterface;
use BackTo\Framework\EventDispatcher\Contracts\StoppableEventInterface;

/**
 * In-memory event dispatcher.
 *
 * Supports both direct listener registration and subscriber-based registration.
 * Listeners are called in descending priority order (higher = earlier).
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /** @var array<class-string, ListenerDescriptor[]> */
    private array $listeners = [];

    /** @var array<class-string, bool> Track which event types have been sorted */
    private array $sorted = [];

    public function dispatch(object $event): object
    {
        $eventClass = $event::class;

        foreach ($this->getListenersForEvent($eventClass) as $descriptor) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            ($descriptor->getListener())($event);
        }

        return $event;
    }

    /**
     * Register a listener for a specific event class.
     *
     * @param class-string $eventClass
     * @param callable $listener
     * @param int $priority Higher = called earlier (default 0)
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        if ($eventClass === '') {
            throw new \InvalidArgumentException('Event class name must not be empty.');
        }

        $this->listeners[$eventClass][] = new ListenerDescriptor($listener, $priority);
        unset($this->sorted[$eventClass]);
    }

    /**
     * Register all listeners declared by a subscriber.
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventClass => $params) {
            if ($eventClass === '') {
                throw new \InvalidArgumentException(sprintf(
                    'Subscriber %s declares an empty event class name.',
                    $subscriber::class
                ));
            }

            // 'methodName'
            if (is_string($params)) {
                $this->addListener($eventClass, [$subscriber, $params]);
                continue;
            }

            // ['methodName', priority] or [['methodName', priority], ...]
            if (is_array($params)) {
                $this->addSubscriberParams($subscriber, $eventClass, $params);
            }
        }
    }

    /**
     * Remove a listener for a specific event class.
     *
     * @param class-string $eventClass
     * @param callable $listener
     */
    public function removeListener(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        $this->listeners[$eventClass] = array_values(array_filter(
            $this->listeners[$eventClass],
            static fn (ListenerDescriptor $d): bool => $d->getListener() !== $listener
        ));

        if ($this->listeners[$eventClass] === []) {
            unset($this->listeners[$eventClass], $this->sorted[$eventClass]);
        } else {
            unset($this->sorted[$eventClass]);
        }
    }

    /**
     * Check if any listeners are registered for a given event class.
     *
     * @param class-string $eventClass
     */
    public function hasListeners(string $eventClass): bool
    {
        return isset($this->listeners[$eventClass]) && $this->listeners[$eventClass] !== [];
    }

    /**
     * @param class-string $eventClass
     * @return ListenerDescriptor[]
     */
    private function getListenersForEvent(string $eventClass): array
    {
        if (!isset($this->listeners[$eventClass])) {
            return [];
        }

        if (!isset($this->sorted[$eventClass])) {
            usort(
                $this->listeners[$eventClass],
                static fn (ListenerDescriptor $a, ListenerDescriptor $b): int => $b->getPriority() <=> $a->getPriority()
            );
            $this->sorted[$eventClass] = true;
        }

        return $this->listeners[$eventClass];
    }

    /**
     * @param class-string $eventClass
     * @param array<mixed> $params
     */
    private function addSubscriberParams(EventSubscriberInterface $subscriber, string $eventClass, array $params): void
    {
        // ['methodName', priority]
        if (is_string($params[0] ?? null)) {
            $this->addListener($eventClass, [$subscriber, $params[0]], (int) ($params[1] ?? 0));
            return;
        }

        // [['methodName', priority], ['otherMethod', priority]]
        foreach ($params as $listenerDef) {
            if (!is_array($listenerDef)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid listener definition in subscriber %s for event %s.',
                    $subscriber::class,
                    $eventClass
                ));
            }

            $this->addListener($eventClass, [$subscriber, $listenerDef[0]], (int) ($listenerDef[1] ?? 0));
        }
    }
}
