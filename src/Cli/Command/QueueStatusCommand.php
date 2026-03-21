<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Cli\Contracts\CliOutputInterface;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\Entity\JobStatus;

/**
 * WP-CLI command: wp backto:queue
 *
 * Displays queue status: pending, running, failed jobs and active groups.
 */
final class QueueStatusCommand
{
    private readonly QueueQueryInterface $query;
    private readonly CliOutputInterface $output;

    public function __construct(QueueQueryInterface $query, CliOutputInterface $output)
    {
        $this->query = $query;
        $this->output = $output;
    }

    /**
     * Display queue status overview.
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
        $pending = $this->query->countByStatus(JobStatus::Pending);
        $running = $this->query->countByStatus(JobStatus::Running);
        $failed = $this->query->countByStatus(JobStatus::Failed);
        $groups = $this->query->getActiveGroups();

        $format = $assocArgs['format'] ?? 'table';

        $rows = [
            ['metric' => 'Pending jobs', 'value' => (string) $pending],
            ['metric' => 'Running jobs', 'value' => (string) $running],
            ['metric' => 'Failed jobs', 'value' => (string) $failed],
            ['metric' => 'Active groups', 'value' => \implode(', ', $groups) ?: 'None'],
        ];

        if ($format === 'json') {
            \WP_CLI::line(\json_encode([
                'pending' => $pending,
                'running' => $running,
                'failed' => $failed,
                'active_groups' => $groups,
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));

            return;
        }

        \WP_CLI\Utils\format_items($format, $rows, ['metric', 'value']);

        if ($failed > 0) {
            $this->output->error(\sprintf('%d failed jobs detected.', $failed));
        } else {
            $this->output->success('Queue is healthy.');
        }
    }
}
