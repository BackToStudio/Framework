<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Severity levels for security audit log events.
 *
 * Available for type-safe usage in new code. Existing code using string
 * literals ('info', 'warning', 'critical') remains compatible via ->value.
 */
enum AuditLogSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
}
