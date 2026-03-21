<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\Contracts\MetricStoreInterface;

/**
 * WordPress options-based metric store with in-memory write buffer.
 *
 * All write operations (record, increment) are buffered in memory
 * and only persisted to the database when flush() is called.
 * Read operations (latest, history, summary) merge the buffer with
 * persisted data so callers always see a consistent view.
 *
 * Call flush() once at shutdown to persist all buffered metrics
 * in a single get_option + update_option roundtrip.
 *
 * Suitable for low-to-medium volume metrics (dashboards, health checks,
 * queue depth). For high-volume metrics, a custom table implementation
 * should be used.
 */
final class WordPressMetricStore implements MetricStoreInterface
{
    private const OPTION_KEY = 'backto_metrics';
    private const MAX_ENTRIES_PER_METRIC = 1000;

    /** @var array<string, array<int, array{value: float, type: string, tags: array<string, string>, recorded_at: int}>> */
    private array $buffer = [];

    private bool $dirty = false;

    public function record(string $name, float $value, string $type = 'gauge', array $tags = []): void
    {
        if (!isset($this->buffer[$name])) {
            $this->buffer[$name] = [];
        }

        $this->buffer[$name][] = [
            'value' => $value,
            'type' => $type,
            'tags' => $tags,
            'recorded_at' => \time(),
        ];

        $this->dirty = true;
    }

    public function increment(string $name, float $amount = 1.0, array $tags = []): void
    {
        $current = 0.0;

        // Check buffer first, then persisted data
        if (isset($this->buffer[$name]) && $this->buffer[$name] !== []) {
            $last = end($this->buffer[$name]);
            if ($last['type'] === 'counter') {
                $current = $last['value'];
            }
        } else {
            $persisted = $this->load();
            if (isset($persisted[$name]) && $persisted[$name] !== []) {
                $last = end($persisted[$name]);
                if ($last['type'] === 'counter') {
                    $current = $last['value'];
                }
            }
        }

        $this->record($name, $current + $amount, 'counter', $tags);
    }

    public function latest(string $name): ?array
    {
        $merged = $this->getMerged();

        if (!isset($merged[$name]) || $merged[$name] === []) {
            return null;
        }

        $last = end($merged[$name]);

        return [
            'value' => $last['value'],
            'type' => $last['type'],
            'recorded_at' => $last['recorded_at'],
        ];
    }

    public function history(string $name, int $since, int $until = 0): array
    {
        $merged = $this->getMerged();
        $until = $until > 0 ? $until : \time();

        if (!isset($merged[$name])) {
            return [];
        }

        $results = [];
        foreach ($merged[$name] as $entry) {
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
        $merged = $this->getMerged();
        $summaries = [];

        foreach ($merged as $name => $entries) {
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
        // Flush buffer first so purge operates on complete data
        $this->flush();

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
     * Persist all buffered metrics to the database in a single write.
     *
     * Safe to call multiple times — no-op when the buffer is clean.
     */
    public function flush(): void
    {
        if (!$this->dirty) {
            return;
        }

        $metrics = $this->load();

        foreach ($this->buffer as $name => $entries) {
            if (!isset($metrics[$name])) {
                $metrics[$name] = [];
            }

            foreach ($entries as $entry) {
                $metrics[$name][] = $entry;
            }

            // Cap per-metric entries to prevent unbounded growth
            if (\count($metrics[$name]) > self::MAX_ENTRIES_PER_METRIC) {
                $metrics[$name] = \array_slice($metrics[$name], -self::MAX_ENTRIES_PER_METRIC);
            }
        }

        $this->save($metrics);
        $this->buffer = [];
        $this->dirty = false;
    }

    /**
     * @return bool Whether there are unflushed metrics in the buffer.
     */
    public function isDirty(): bool
    {
        return $this->dirty;
    }

    /**
     * Merge persisted data with the in-memory buffer for reads.
     *
     * @return array<string, array<int, array{value: float, type: string, tags: array<string, string>, recorded_at: int}>>
     */
    private function getMerged(): array
    {
        $persisted = $this->load();

        if ($this->buffer === []) {
            return $persisted;
        }

        foreach ($this->buffer as $name => $entries) {
            if (!isset($persisted[$name])) {
                $persisted[$name] = [];
            }
            foreach ($entries as $entry) {
                $persisted[$name][] = $entry;
            }
        }

        return $persisted;
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
