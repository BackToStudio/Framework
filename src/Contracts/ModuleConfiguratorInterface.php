<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Contract for module configurators used in config/*.php files.
 *
 * Implementations provide a fluent API for setting module parameters
 * without exposing internal parameter key names.
 */
interface ModuleConfiguratorInterface
{
    /**
     * @return array<string, mixed> Parameter key => value pairs to set on the container.
     */
    public function toParameters(): array;
}
