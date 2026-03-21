<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Cli\Contracts\CliOutputInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Queue\QueueMaintenance;

/**
 * WP-CLI command: wp backto:maintenance
 *
 * Run maintenance operations: DB cleanup, metric purge, queue rescue.
 */
final class MaintenanceCommand
{
    private readonly DatabaseOptimizerInterface $dbOptimizer;
    private readonly MetricStoreInterface $metricStore;
    private readonly QueueMaintenance $queueMaintenance;
    private readonly CliOutputInterface $output;

    public function __construct(
        DatabaseOptimizerInterface $dbOptimizer,
        MetricStoreInterface $metricStore,
        QueueMaintenance $queueMaintenance,
        CliOutputInterface $output,
    ) {
        $this->dbOptimizer = $dbOptimizer;
        $this->metricStore = $metricStore;
        $this->queueMaintenance = $queueMaintenance;
        $this->output = $output;
    }

    /**
     * Run all maintenance tasks.
     *
     * ## OPTIONS
     *
     * [--db-only]
     * : Only run database cleanup and optimization.
     *
     * [--purge-metrics]
     * : Purge metrics older than N days (default: 30).
     *
     * [--days=<days>]
     * : Number of days to retain for metric purge.
     * ---
     * default: 30
     * ---
     *
     * [--optimize-tables]
     * : Also run OPTIMIZE TABLE on all WordPress tables.
     *
     * @param array<int, string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $dbOnly = isset($assocArgs['db-only']);
        $purgeMetrics = isset($assocArgs['purge-metrics']);
        $optimizeTables = isset($assocArgs['optimize-tables']);
        $days = (int) ($assocArgs['days'] ?? 30);

        // Database cleanup
        \WP_CLI::log('Running database cleanup...');
        $results = $this->dbOptimizer->cleanup();

        $rows = [];
        $totalCleaned = 0;
        foreach ($results as $task => $count) {
            $rows[] = ['task' => \str_replace('_', ' ', \ucfirst($task)), 'cleaned' => $count];
            $totalCleaned += $count;
        }

        \WP_CLI\Utils\format_items('table', $rows, ['task', 'cleaned']);
        \WP_CLI::log(\sprintf('Total: %d items cleaned.', $totalCleaned));

        if ($optimizeTables) {
            \WP_CLI::log('Optimizing database tables...');
            $tables = $this->dbOptimizer->optimizeTables();
            \WP_CLI::log(\sprintf('%d tables optimized.', $tables));
        }

        if ($dbOnly) {
            $this->output->success('Database maintenance complete.');

            return;
        }

        // Queue maintenance
        \WP_CLI::log('Rescuing stuck queue jobs...');
        $this->queueMaintenance->rescueStuckJobs();

        \WP_CLI::log('Cleaning up old queue jobs...');
        $this->queueMaintenance->cleanupJobs();

        // Metric purge
        if ($purgeMetrics) {
            \WP_CLI::log(\sprintf('Purging metrics older than %d days...', $days));
            $purged = $this->metricStore->purge($days);
            \WP_CLI::log(\sprintf('%d metric entries purged.', $purged));
        }

        $this->output->success('All maintenance tasks complete.');
    }
}
