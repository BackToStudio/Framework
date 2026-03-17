<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Infrastructure;

use BackTo\Framework\Cache\Contracts\TransientCleanerInterface;

/**
 * WordPress adapter for bulk-clearing transients via $wpdb.
 */
class WordPressTransientCleaner implements TransientCleanerInterface
{
    public function clearByPrefix(string $prefix): bool
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $wpdb->esc_like($prefix) . '%',
                '_transient_timeout_' . $wpdb->esc_like($prefix) . '%'
            )
        );

        return true;
    }
}
