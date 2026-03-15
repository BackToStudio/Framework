<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Infrastructure;

use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;

class CookieConsentStorage implements ConsentStorageInterface
{
    private const COOKIE_NAME = 'gdpr_consent';

    /**
     * @return array<string, bool>
     */
    public function getConsent(): array
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return [];
        }

        $decoded = json_decode($_COOKIE[self::COOKIE_NAME], true);

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
