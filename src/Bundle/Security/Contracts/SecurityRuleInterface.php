<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

use BackTo\Framework\Contracts\HookInterface;

/**
 * Marker interface for security rules auto-registered in the SecurityRuleRegistry.
 */
interface SecurityRuleInterface extends HookInterface
{
    public function getName(): string;
}
