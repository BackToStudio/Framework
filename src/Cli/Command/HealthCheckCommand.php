<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Cli\Contracts\CliOutputInterface;
use BackTo\Framework\Observability\HealthCheckRegistry;

/**
 * WP-CLI command: wp backto:health
 *
 * Runs all registered health checks and displays results as a table.
 */
final class HealthCheckCommand
{
    private readonly HealthCheckRegistry $registry;
    private readonly CliOutputInterface $output;

    public function __construct(HealthCheckRegistry $registry, CliOutputInterface $output)
    {
        $this->registry = $registry;
        $this->output = $output;
    }

    /**
     * Run all health checks and display results.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format (table, json, csv).
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     * ---
     *
     * @param array<int, string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $results = $this->registry->runAll();

        if ($results === []) {
            $this->output->error('No health checks registered.');

            return;
        }

        $format = $assocArgs['format'] ?? 'table';
        $rows = [];
        $allHealthy = true;

        foreach ($results as $name => $result) {
            $rows[] = [
                'component' => $name,
                'status' => \strtoupper($result->getStatus()),
                'message' => $result->getMessage(),
            ];

            if (!$result->isHealthy()) {
                $allHealthy = false;
            }
        }

        if ($format === 'json') {
            \WP_CLI::line(\json_encode($rows, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));

            return;
        }

        \WP_CLI\Utils\format_items($format, $rows, ['component', 'status', 'message']);

        if ($allHealthy) {
            $this->output->success(\sprintf('All %d health checks passed.', \count($results)));
        } else {
            $this->output->error('Some health checks failed. See table above.');
        }
    }
}
