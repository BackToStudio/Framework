<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * PSR-20 Clock interface.
 *
 * Port interface for time abstraction, enabling deterministic
 * testing of time-dependent code (cache TTL, rate limiting, etc.).
 *
 * @see https://www.php-fig.org/psr/psr-20/
 */
interface ClockInterface extends \Psr\Clock\ClockInterface
{
}
