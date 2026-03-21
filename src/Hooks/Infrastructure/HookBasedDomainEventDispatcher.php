<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\DomainEventDispatcherInterface;
use BackTo\Framework\Contracts\DomainEventInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;

/**
 * Domain event dispatcher backed by WordPress hooks.
 *
 * Translates domain events into WordPress action hooks using the convention:
 *   backto.domain_event.{snake_case_class_name}
 *
 * This enables any WordPress plugin/theme to listen to domain events
 * through the standard add_action() mechanism.
 */
final class HookBasedDomainEventDispatcher implements DomainEventDispatcherInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function dispatch(DomainEventInterface $event): void
    {
        $eventName = self::resolveEventName($event);

        $this->hookDispatcher->doAction($eventName, $event);
    }

    /**
     * Resolve the hook name from a domain event class.
     *
     * JobCompleted → backto.domain_event.job_completed
     * PostPublished → backto.domain_event.post_published
     */
    private static function resolveEventName(DomainEventInterface $event): string
    {
        $className = (new \ReflectionClass($event))->getShortName();
        $snakeCase = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($className)));

        return 'backto.domain_event.' . $snakeCase;
    }
}
