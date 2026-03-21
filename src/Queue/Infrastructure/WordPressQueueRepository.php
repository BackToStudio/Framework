<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Infrastructure;

use BackTo\Framework\Contracts\DatabaseConnectionInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\Factory\JobFactory;

/**
 * WordPress adapter for queue persistence using a custom database table.
 *
 * Delegates all database access through DatabaseConnectionInterface,
 * eliminating direct coupling to the global $wpdb.
 */
final class WordPressQueueRepository implements QueueRepositoryInterface
{
    private readonly JobFactory $factory;
    private readonly DatabaseConnectionInterface $db;
    private readonly string $table;

    public function __construct(JobFactory $factory, DatabaseConnectionInterface $db)
    {
        $this->factory = $factory;
        $this->db = $db;
        $this->table = $db->prefix() . 'backto_queue_jobs';
    }

    public function createTable(): void
    {
        $charset = $this->db->charsetCollate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_key VARCHAR(255) NOT NULL,
            job_group VARCHAR(255) NOT NULL DEFAULT 'default',
            payload LONGTEXT NOT NULL DEFAULT '[]',
            payload_hash CHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            max_retries INT UNSIGNED NOT NULL DEFAULT 3,
            last_error TEXT NOT NULL DEFAULT '',
            claim_token VARCHAR(64) NOT NULL DEFAULT '',
            interval_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            scheduled_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            claimed_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status_group_scheduled (status, job_group, scheduled_at),
            KEY idx_job_key_status (job_key, status),
            KEY idx_status_claimed (status, claimed_at),
            KEY idx_payload_hash (job_key, payload_hash, status),
            KEY idx_status_completed (status, completed_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        \dbDelta($sql);
    }

    public function dropTable(): void
    {
        $this->db->query("DROP TABLE IF EXISTS {$this->table}");
    }

    public function enqueue(Job $job): int
    {
        $payloadJson = \wp_json_encode($job->getPayload()) ?: '[]';
        $payloadHash = $job->getPayloadHash() ?: \md5($payloadJson);

        $this->db->insert(
            $this->table,
            [
                'job_key' => $job->getKey(),
                'job_group' => $job->getGroup(),
                'payload' => $payloadJson,
                'payload_hash' => $payloadHash,
                'status' => $job->getStatus()->value,
                'attempts' => $job->getAttempts(),
                'max_retries' => $job->getMaxRetries(),
                'interval_seconds' => $job->getIntervalSeconds(),
                'scheduled_at' => $job->getScheduledAt()?->format('Y-m-d H:i:s') ?? \gmdate('Y-m-d H:i:s'),
                'created_at' => $job->getCreatedAt()?->format('Y-m-d H:i:s') ?? \gmdate('Y-m-d H:i:s'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s']
        );

        return $this->db->lastInsertId();
    }

    public function claimNextPending(string $group = 'default'): ?Job
    {
        $now = \gmdate('Y-m-d H:i:s');
        $claimToken = \bin2hex(\random_bytes(16));

        $updated = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = %s, claimed_at = %s, claim_token = %s
                 WHERE status = %s
                   AND job_group = %s
                   AND scheduled_at <= %s
                 ORDER BY scheduled_at ASC, id ASC
                 LIMIT 1",
                JobStatus::Running->value,
                $now,
                $claimToken,
                JobStatus::Pending->value,
                $group,
                $now
            )
        );

        if ($updated === 0 || $updated === false) {
            return null;
        }

        $row = $this->db->getRow(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE claim_token = %s LIMIT 1",
                $claimToken
            )
        );

        if ($row === null) {
            return null;
        }

        return $this->factory->fromRow($row);
    }

    public function markCompleted(int $jobId): void
    {
        $this->db->update(
            $this->table,
            [
                'status' => JobStatus::Completed->value,
                'completed_at' => \gmdate('Y-m-d H:i:s'),
                'claim_token' => '',
            ],
            ['id' => $jobId],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    public function markFailed(int $jobId, string $errorMessage): void
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = %s, last_error = %s, claim_token = '', attempts = attempts + 1
                 WHERE id = %d",
                JobStatus::Failed->value,
                JobFactory::sanitizeError($errorMessage),
                $jobId
            )
        );
    }

    public function release(int $jobId): void
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = %s, claimed_at = NULL, claim_token = ''
                 WHERE id = %d",
                JobStatus::Pending->value,
                $jobId
            )
        );
    }

    public function find(int $jobId): ?Job
    {
        $row = $this->db->getRow(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $jobId)
        );

        if ($row === null) {
            return null;
        }

        return $this->factory->fromRow($row);
    }

    public function findByStatus(JobStatus $status, int $limit = 20, int $offset = 0): array
    {
        $rows = $this->db->getResults(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $status->value,
                $limit,
                $offset
            )
        );

        return \array_map(fn (array $row): Job => $this->factory->fromRow($row), $rows);
    }

    public function countByStatus(JobStatus $status): int
    {
        return (int) $this->db->getVar(
            $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = %s", $status->value)
        );
    }

    public function cleanup(int $olderThanSeconds = 86400): int
    {
        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $olderThanSeconds);

        return (int) $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$this->table} WHERE status = %s AND completed_at < %s",
                JobStatus::Completed->value,
                $cutoff
            )
        );
    }

    public function cleanupFailed(int $olderThanSeconds = 604800): int
    {
        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $olderThanSeconds);

        return (int) $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE status = %s AND attempts >= max_retries AND updated_at < %s",
                JobStatus::Failed->value,
                $cutoff
            )
        );
    }

    public function cancel(int $jobId): void
    {
        $this->db->update(
            $this->table,
            ['status' => JobStatus::Cancelled->value],
            ['id' => $jobId, 'status' => JobStatus::Pending->value],
            ['%s'],
            ['%d', '%s']
        );
    }

    public function rescueStuck(int $timeoutSeconds = 300): int
    {
        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $timeoutSeconds);

        return (int) $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = %s, claimed_at = NULL, claim_token = ''
                 WHERE status = %s AND claimed_at < %s",
                JobStatus::Pending->value,
                JobStatus::Running->value,
                $cutoff
            )
        );
    }

    public function hasPendingDuplicate(string $jobKey, string $payloadHash): bool
    {
        $count = (int) $this->db->getVar(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table}
                 WHERE job_key = %s AND payload_hash = %s AND status = %s",
                $jobKey,
                $payloadHash,
                JobStatus::Pending->value
            )
        );

        return $count > 0;
    }

    public function getActiveGroups(): array
    {
        return $this->db->getCol(
            $this->db->prepare(
                "SELECT DISTINCT job_group FROM {$this->table} WHERE status IN (%s, %s)",
                JobStatus::Pending->value,
                JobStatus::Running->value
            )
        );
    }
}
