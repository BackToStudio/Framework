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

    /** @var string Pattern for valid HTML tag names (alphanumeric + hyphens) */
    private const VALID_TAG_PATTERN = '/^[a-z][a-z0-9\-]*$/i';

    /**
     * @param array<string, array<string, bool>> $allowedHtml
     *
     * @throws \InvalidArgumentException If allowedHtml contains invalid tag names.
     */
    public function sanitizeHtml(string $input, array $allowedHtml = []): string
    {
        if ($allowedHtml === []) {
            return wp_kses_post($input);
        }

        // Validate that allowed_html keys are legitimate HTML tag names
        // to prevent bypasses via user-controlled tag names.
        foreach (array_keys($allowedHtml) as $tag) {
            if (preg_match(self::VALID_TAG_PATTERN, $tag) !== 1) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid HTML tag name "%s" in allowedHtml.', $tag)
                );
            }
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
