<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

use BackTo\Framework\EventDispatcher\Contracts\StoppableEventInterface;
use BackToVendor\Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

/**
 * Base event class with propagation stopping support.
 *
 * Extends Symfony's Event (which provides stopPropagation/isPropagationStopped)
 * and implements the framework's StoppableEventInterface port.
 *
 * Extend this class for convenience, or implement StoppableEventInterface directly.
 */
class Event extends SymfonyEvent implements StoppableEventInterface
{
}
