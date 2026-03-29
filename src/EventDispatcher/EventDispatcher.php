<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventSubscriberInterface;
use BackToVendor\Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

/**
 * Event dispatcher backed by Symfony's EventDispatcher component.
 *
 * Delegates listener management and event dispatching to the vendor-scoped
 * Symfony EventDispatcher. The framework's EventSubscriberInterface is
 * translated into Symfony listener registrations.
 */
final class EventDispatcher implements EventDispatcherInterface
{
    private readonly SymfonyEventDispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new SymfonyEventDispatcher();
    }

    public function dispatch(object $event): object
    {
        return $this->dispatcher->dispatch($event, $event::class);
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

        $this->dispatcher->addListener($eventClass, $listener, $priority);
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
        $this->dispatcher->removeListener($eventClass, $listener);
    }

    /**
     * Check if any listeners are registered for a given event class.
     *
     * @param class-string $eventClass
     */
    public function hasListeners(string $eventClass): bool
    {
        return $this->dispatcher->hasListeners($eventClass);
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
