<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\Contracts\MetricStoreInterface;

/**
 * WordPress options-based metric store.
 *
 * Stores metrics as a serialized array in a single WordPress option,
 * partitioned by day for efficient purging. Suitable for low-to-medium
 * volume metrics (dashboards, health checks, queue depth).
 *
 * For high-volume metrics, a custom table implementation should be used.
 */
final class WordPressMetricStore implements MetricStoreInterface
{
    private const OPTION_KEY = 'backto_metrics';
    private const MAX_ENTRIES_PER_METRIC = 1000;

    public function record(string $name, float $value, string $type = 'gauge', array $tags = []): void
    {
        $metrics = $this->load();

        if (!isset($metrics[$name])) {
            $metrics[$name] = [];
        }

        $metrics[$name][] = [
            'value' => $value,
            'type' => $type,
            'tags' => $tags,
            'recorded_at' => \time(),
        ];

        // Cap per-metric entries to prevent unbounded growth
        if (\count($metrics[$name]) > self::MAX_ENTRIES_PER_METRIC) {
            $metrics[$name] = \array_slice($metrics[$name], -self::MAX_ENTRIES_PER_METRIC);
        }

        $this->save($metrics);
    }

    public function increment(string $name, float $amount = 1.0, array $tags = []): void
    {
        $metrics = $this->load();
        $current = 0.0;

        if (isset($metrics[$name]) && $metrics[$name] !== []) {
            $last = end($metrics[$name]);
            if ($last['type'] === 'counter') {
                $current = $last['value'];
            }
        }

        $this->record($name, $current + $amount, 'counter', $tags);
    }

    public function latest(string $name): ?array
    {
        $metrics = $this->load();

        if (!isset($metrics[$name]) || $metrics[$name] === []) {
            return null;
        }

        $last = end($metrics[$name]);

        return [
            'value' => $last['value'],
            'type' => $last['type'],
            'recorded_at' => $last['recorded_at'],
        ];
    }

    public function history(string $name, int $since, int $until = 0): array
    {
        $metrics = $this->load();
        $until = $until > 0 ? $until : \time();

        if (!isset($metrics[$name])) {
            return [];
        }

        $results = [];
        foreach ($metrics[$name] as $entry) {
            if ($entry['recorded_at'] >= $since && $entry['recorded_at'] <= $until) {
                $results[] = [
                    'name' => $name,
                    'value' => $entry['value'],
                    'type' => $entry['type'],
                    'tags' => $entry['tags'] ?? [],
                    'recorded_at' => $entry['recorded_at'],
                ];
            }
        }

        return $results;
    }

    public function summary(int $since): array
    {
        $metrics = $this->load();
        $summaries = [];

        foreach ($metrics as $name => $entries) {
            $values = [];
            foreach ($entries as $entry) {
                if ($entry['recorded_at'] >= $since) {
                    $values[] = $entry['value'];
                }
            }

            if ($values === []) {
                continue;
            }

            $summaries[$name] = [
                'current' => end($values),
                'min' => \min($values),
                'max' => \max($values),
                'avg' => \round(\array_sum($values) / \count($values), 3),
                'count' => \count($values),
            ];
        }

        return $summaries;
    }

    public function purge(int $days = 30): int
    {
        $metrics = $this->load();
        $cutoff = \time() - ($days * 86400);
        $purged = 0;

        foreach ($metrics as $name => $entries) {
            $before = \count($entries);
            $metrics[$name] = \array_values(\array_filter(
                $entries,
                static fn(array $entry): bool => $entry['recorded_at'] >= $cutoff,
            ));
            $purged += $before - \count($metrics[$name]);

            if ($metrics[$name] === []) {
                unset($metrics[$name]);
            }
        }

        $this->save($metrics);

        return $purged;
    }

    /**
     * @return array<string, array<int, array{value: float, type: string, tags: array<string, string>, recorded_at: int}>>
     */
    private function load(): array
    {
        $raw = \get_option(self::OPTION_KEY, []);

        return \is_array($raw) ? $raw : [];
    }

    /**
     * @param array<string, array<int, array{value: float, type: string, tags: array<string, string>, recorded_at: int}>> $metrics
     */
    private function save(array $metrics): void
    {
        \update_option(self::OPTION_KEY, $metrics, false);
    }
}
