<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for input sanitization.
 */
interface InputSanitizerInterface
{
    public function sanitizeText(string $input): string;

    public function sanitizeEmail(string $input): string;

    public function sanitizeUrl(string $input): string;

    public function sanitizeFileName(string $input): string;

    /**
     * Sanitize HTML content with allowed tags.
     *
     * @param array<string, array<string, bool>> $allowedHtml
     */
    public function sanitizeHtml(string $input, array $allowedHtml = []): string;

    public function sanitizeTextarea(string $input): string;

    public function sanitizeKey(string $input): string;
}
