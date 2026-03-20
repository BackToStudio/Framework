<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for IP-based access control.
 */
interface IPAccessControlInterface
{
    public function addToWhitelist(string $ip): self;

    public function addToBlacklist(string $ip): self;

    public function isAllowed(string $ip): bool;

    public function isBlocked(string $ip): bool;

    
    public function getWhitelist(): array;

    
    public function getBlacklist(): array;
}
