<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Contracts;

interface ConsentStorageInterface
{
    /**
     * @return array<string, bool>
     */
    public function getConsent(): array;

    public function hasConsent(string $categoryKey): bool;

    public function isConsentGiven(): bool;
}
