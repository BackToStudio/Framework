<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\OutputEscaperInterface;

use function esc_attr;
use function esc_html;
use function esc_js;
use function esc_textarea;
use function esc_url;

/**
 * WordPress adapter for output escaping.
 */
final class WordPressOutputEscaper implements OutputEscaperInterface
{
    public function html(string $input): string
    {
        return esc_html($input);
    }

    public function attr(string $input): string
    {
        return esc_attr($input);
    }

    public function url(string $input): string
    {
        return esc_url($input);
    }

    public function js(string $input): string
    {
        return esc_js($input);
    }

    public function textarea(string $input): string
    {
        return esc_textarea($input);
    }
}
