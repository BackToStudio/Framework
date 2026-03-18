<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Port interface for scheduling and managing cron events.
 *
 * Abstracts wp_next_scheduled / wp_schedule_event / wp_schedule_single_event /
 * wp_clear_scheduled_hook / spawn_cron so that application-layer code does
 * not call WordPress functions directly.
 */
interface CronSchedulerInterface
{
    public function isScheduled(string $hook): bool;

    public function scheduleRecurring(string $hook, string $recurrence, int $timestamp = 0): void;

    public function scheduleSingle(string $hook, int $timestamp, array $args = []): void;

    public function clear(string $hook, array $args = []): void;

    /**
     * Trigger cron execution immediately (non-blocking).
     */
    public function spawn(): void;
}
