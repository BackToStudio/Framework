<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for persisting login location history.
 */
interface LoginLocationRepositoryInterface
{
    /**
     * @param array<string, mixed> $locationData IP, country, user agent, etc.
     */
    public function recordLogin(int $userId, array $locationData): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLoginHistory(int $userId, int $limit = 10): array;

    /**
     * @return string[] List of known country codes for this user
     */
    public function getKnownCountries(int $userId): array;
}
