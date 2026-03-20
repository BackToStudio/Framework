<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Infrastructure;

use BackTo\Framework\Bundle\Security\Contracts\LoginLocationRepositoryInterface;

use function get_user_meta;
use function update_user_meta;

/**
 * WordPress adapter for login location persistence using user meta.
 */
final class WordPressLoginLocationRepository implements LoginLocationRepositoryInterface
{
    private const META_HISTORY = '_backto_login_history';
    private const META_COUNTRIES = '_backto_known_countries';
    private const MAX_HISTORY_ENTRIES = 50;

    /**
     * @param array<string, mixed> $locationData
     */
    public function recordLogin(int $userId, array $locationData): void
    {
        // Update history
        /** @var mixed $history */
        $history = get_user_meta($userId, self::META_HISTORY, true);
        $history = is_array($history) ? $history : [];

        array_unshift($history, $locationData);

        if (count($history) > self::MAX_HISTORY_ENTRIES) {
            $history = array_slice($history, 0, self::MAX_HISTORY_ENTRIES);
        }

        update_user_meta($userId, self::META_HISTORY, $history);

        // Update known countries
        $country = (string) ($locationData['country'] ?? 'unknown');

        if ($country !== 'unknown') {
            /** @var mixed $countries */
            $countries = get_user_meta($userId, self::META_COUNTRIES, true);
            $countries = is_array($countries) ? $countries : [];

            if (! in_array($country, $countries, true)) {
                $countries[] = $country;
                update_user_meta($userId, self::META_COUNTRIES, $countries);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLoginHistory(int $userId, int $limit = 10): array
    {
        /** @var mixed $history */
        $history = get_user_meta($userId, self::META_HISTORY, true);
        $history = is_array($history) ? $history : [];

        return array_slice($history, 0, $limit);
    }

    
    public function getKnownCountries(int $userId): array
    {
        /** @var mixed $countries */
        $countries = get_user_meta($userId, self::META_COUNTRIES, true);

        return is_array($countries) ? $countries : [];
    }
}
