<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Infrastructure;

use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;

use function spawn_cron;
use function wp_clear_scheduled_hook;
use function wp_next_scheduled;
use function wp_schedule_event;
use function wp_schedule_single_event;

/**
 * WordPress adapter for WP-Cron scheduling.
 */
final class WordPressCronScheduler implements CronSchedulerInterface
{
    public function isScheduled(string $hook): bool
    {
        return (bool) wp_next_scheduled($hook);
    }

    public function scheduleRecurring(string $hook, string $recurrence, int $timestamp = 0): void
    {
        wp_schedule_event($timestamp ?: time(), $recurrence, $hook);
    }

    public function scheduleSingle(string $hook, int $timestamp, array $args = []): void
    {
        wp_schedule_single_event($timestamp, $hook, $args);
    }

    public function clear(string $hook, array $args = []): void
    {
        if ($args !== []) {
            wp_clear_scheduled_hook($hook, $args);
        } else {
            wp_clear_scheduled_hook($hook);
        }
    }

    public function spawn(): void
    {
        spawn_cron();
    }
}
