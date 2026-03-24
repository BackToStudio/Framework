<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Contracts;

/**
 * Marker interface for event subscribers.
 *
 * Subscribers declare which events they listen to via getSubscribedEvents().
 * The compiler pass autoconfigures services implementing this interface
 * and registers them with the EventDispatcher.
 */
interface EventSubscriberInterface
{
    /**
     * Return the events this subscriber listens to.
     *
     * Each key is an event class name. The value can be:
     *   - A method name string:        ['PostPublished' => 'onPostPublished']
     *   - An array of [method, priority]: ['PostPublished' => ['onPostPublished', 10]]
     *   - An array of arrays for multiple listeners:
     *       ['PostPublished' => [['onPostPublished', 10], ['logEvent', -10]]]
     *
     * @return array<class-string, string|array{0: string, 1?: int}|array<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array;
}
