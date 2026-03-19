<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress escaping functions.
 *
 * Replaces direct calls to esc_url(), esc_attr(), esc_html() in domain code.
 */
interface EscaperInterface
{
    public function escUrl(string $url): string;

    public function escAttr(string $text): string;

    public function escHtml(string $text): string;
}
