<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\RateLimiterRepositoryInterface;

use function get_transient;
use function set_transient;

/**
 * WordPress adapter for rate limiter persistence using transients.
 */
final class WordPressRateLimiterRepository implements RateLimiterRepositoryInterface
{
    private const PREFIX = 'backto_rl_';

    public function increment(string $key, int $windowSeconds): int
    {
        $transientKey = self::PREFIX . md5($key);

        /** @var mixed $data */
        $data = get_transient($transientKey);

        if (! is_array($data) || ! isset($data['count'])) {
            $data = ['count' => 0, 'expires' => time() + $windowSeconds];
        }

        $data['count']++;
        $remaining = max(0, (int) $data['expires'] - time());
        set_transient($transientKey, $data, $remaining > 0 ? $remaining : $windowSeconds);

        return (int) $data['count'];
    }

    public function getHits(string $key): int
    {
        $transientKey = self::PREFIX . md5($key);

        /** @var mixed $data */
        $data = get_transient($transientKey);

        if (! is_array($data) || ! isset($data['count'])) {
            return 0;
        }

        return (int) $data['count'];
    }

    public function getTtl(string $key): int
    {
        $transientKey = self::PREFIX . md5($key);

        /** @var mixed $data */
        $data = get_transient($transientKey);

        if (! is_array($data) || ! isset($data['expires'])) {
            return 0;
        }

        return max(0, (int) $data['expires'] - time());
    }
}
