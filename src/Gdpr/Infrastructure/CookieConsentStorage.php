<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Infrastructure;

use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;

final class CookieConsentStorage implements ConsentStorageInterface
{
    private const COOKIE_NAME = 'gdpr_consent';

    /** @var int Maximum cookie value length to process (4KB — browser limit) */
    private const MAX_COOKIE_LENGTH = 4096;

    /**
     * @return array<string, bool>
     */
    public function getConsent(): array
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return [];
        }

        $raw = $_COOKIE[self::COOKIE_NAME];

        if (!is_string($raw) || strlen($raw) > self::MAX_COOKIE_LENGTH) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function hasConsent(string $categoryKey): bool
    {
        return ($this->getConsent()[$categoryKey] ?? false) === true;
    }

    public function isConsentGiven(): bool
    {
        return isset($_COOKIE[self::COOKIE_NAME]);
    }
}
