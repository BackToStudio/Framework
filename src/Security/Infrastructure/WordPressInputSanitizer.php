<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\InputSanitizerInterface;

use function sanitize_email;
use function sanitize_file_name;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_url;
use function wp_kses;
use function wp_kses_post;

/**
 * WordPress adapter for input sanitization.
 */
final class WordPressInputSanitizer implements InputSanitizerInterface
{
    public function sanitizeText(string $input): string
    {
        return sanitize_text_field($input);
    }

    public function sanitizeEmail(string $input): string
    {
        return sanitize_email($input);
    }

    public function sanitizeUrl(string $input): string
    {
        return sanitize_url($input);
    }

    public function sanitizeFileName(string $input): string
    {
        return sanitize_file_name($input);
    }

    /**
     * @param array<string, array<string, bool>> $allowedHtml
     */
    public function sanitizeHtml(string $input, array $allowedHtml = []): string
    {
        if ($allowedHtml === []) {
            return wp_kses_post($input);
        }

        return wp_kses($input, $allowedHtml);
    }

    public function sanitizeTextarea(string $input): string
    {
        return sanitize_textarea_field($input);
    }

    public function sanitizeKey(string $input): string
    {
        return sanitize_key($input);
    }
}
