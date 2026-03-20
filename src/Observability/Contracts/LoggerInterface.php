<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * PSR-3 logger interface.
 *
 * Port interface — domain and application layers depend on this contract.
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
interface LoggerInterface extends \Psr\Log\LoggerInterface
{
}
