<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for WordPress nonce management.
 */
interface NonceManagerInterface
{
    public function create(string $action): string;

    public function verify(string $nonce, string $action): bool;

    public function getFieldName(): string;
}
