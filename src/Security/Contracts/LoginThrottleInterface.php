<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Composite port interface for login attempt throttling.
 *
 * Extends both segregated throttle interfaces for backward compatibility.
 * Prefer depending on the narrowest interface your class actually needs:
 * - IpLoginThrottleInterface: IP-based throttling only
 * - AccountLoginThrottleInterface: account-based throttling only
 */
interface LoginThrottleInterface extends IpLoginThrottleInterface, AccountLoginThrottleInterface
{
}
