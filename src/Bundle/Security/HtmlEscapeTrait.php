<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

/**
 * Provides shared HTML escaping methods for security rules that render output.
 */
trait HtmlEscapeTrait
{
    protected function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
