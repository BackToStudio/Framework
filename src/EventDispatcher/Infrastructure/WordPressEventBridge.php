<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Infrastructure;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;

/**
 * Bridges framework events to WordPress action hooks.
 *
 * When an event is dispatched through the EventDispatcher, this bridge
 * also fires a WordPress action hook, allowing themes and third-party
 * plugins to listen using standard add_action().
 *
 * Hook naming convention:
 *   backto.event.{snake_case_class_name}
 *
 * Example: PostPublished → backto.event.post_published
 */
final class WordPressEventBridge implements EventDispatcherInterface
{
    private readonly EventDispatcherInterface $inner;
    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var array<class-string, string> */
    private static array $hookNameCache = [];

    public function __construct(EventDispatcherInterface $inner, HookDispatcherInterface $hookDispatcher)
    {
        $this->inner = $inner;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function dispatch(object $event): object
    {
        $event = $this->inner->dispatch($event);

        $hookName = self::resolveHookName($event);
        $this->hookDispatcher->doAction($hookName, $event);

        return $event;
    }

    /**
     * Resolve the WordPress hook name from an event class.
     *
     * UserRegistered → backto.event.user_registered
     */
    private static function resolveHookName(object $event): string
    {
        $class = $event::class;

        if (isset(self::$hookNameCache[$class])) {
            return self::$hookNameCache[$class];
        }

        $className = (new \ReflectionClass($event))->getShortName();
        $snakeCase = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($className)));

        return self::$hookNameCache[$class] = 'backto.event.' . $snakeCase;
    }
}
