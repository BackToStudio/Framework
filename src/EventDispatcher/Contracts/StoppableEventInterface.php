<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Contracts;

/**
 * An event whose propagation can be stopped.
 *
 * When isPropagationStopped() returns true, the dispatcher skips
 * all remaining listeners for this event.
 *
 * Extends the PSR-14 StoppableEventInterface (vendor-scoped) so that
 * any framework event implementing this contract is automatically
 * recognized by the underlying Symfony EventDispatcher.
 */
interface StoppableEventInterface extends \BackToVendor\Psr\EventDispatcher\StoppableEventInterface
{
}
