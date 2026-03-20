<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\EscaperInterface;

final class WordPressEscaper implements EscaperInterface
{
    public function escUrl(string $url): string
    {
        return function_exists('esc_url') ? \esc_url($url) : htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    public function escAttr(string $text): string
    {
        return function_exists('esc_attr') ? \esc_attr($text) : htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public function escHtml(string $text): string
    {
        return function_exists('esc_html') ? \esc_html($text) : htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
