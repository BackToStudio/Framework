<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for IP-based access control.
 */
interface IPAccessControlInterface
{
    public function addToWhitelist(string $ip): self;

    public function addToBlacklist(string $ip): self;

    public function isAllowed(string $ip): bool;

    public function isBlocked(string $ip): bool;

    /**
     * @return string[]
     */
    public function getWhitelist(): array;

    /**
     * @return string[]
     */
    public function getBlacklist(): array;
}
