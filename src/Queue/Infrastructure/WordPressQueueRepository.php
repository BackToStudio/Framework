<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Infrastructure;

use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\Factory\JobFactory;

/**
 * WordPress adapter for queue persistence using a custom database table.
 */
final class WordPressQueueRepository implements QueueRepositoryInterface
{
    private readonly JobFactory $factory;
    private readonly string $table;

    public function __construct(JobFactory $factory)
    {
        global $wpdb;

        $this->factory = $factory;
        $this->table = $wpdb->prefix . 'backto_queue_jobs';
    }

    public function createTable(): void
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

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
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
    }

    public function enqueue(Job $job): int
    {
        global $wpdb;

        $payloadJson = \wp_json_encode($job->getPayload()) ?: '[]';
        $payloadHash = $job->getPayloadHash() ?: \md5($payloadJson);

        $wpdb->insert(
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

        return (int) $wpdb->insert_id;
    }

    public function claimNextPending(string $group = 'default'): ?Job
    {
        global $wpdb;

        $now = \gmdate('Y-m-d H:i:s');
        $claimToken = \bin2hex(\random_bytes(16));

        // Atomic claim: UPDATE with WHERE ensures only one worker gets the job.
        // The unique claim_token prevents ambiguity when retrieving the claimed job.
        $updated = $wpdb->query(
            $wpdb->prepare(
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

        // Retrieve by unique claim_token — no ambiguity even with concurrent workers.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE claim_token = %s LIMIT 1",
                $claimToken
            ),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->factory->fromRow($row);
    }

    public function markCompleted(int $jobId): void
    {
        global $wpdb;

        $wpdb->update(
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
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
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
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'status' => JobStatus::Pending->value,
                'claimed_at' => null,
                'claim_token' => '',
            ],
            ['id' => $jobId],
            ['%s', null, '%s'],
            ['%d']
        );
    }

    public function find(int $jobId): ?Job
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $jobId),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->factory->fromRow($row);
    }

    
    public function findByStatus(JobStatus $status, int $limit = 20, int $offset = 0): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $status->value,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        if (!\is_array($rows)) {
            return [];
        }

        return \array_map(fn (array $row): Job => $this->factory->fromRow($row), $rows);
    }

    public function countByStatus(JobStatus $status): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = %s", $status->value)
        );
    }

    public function cleanup(int $olderThanSeconds = 86400): int
    {
        global $wpdb;

        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $olderThanSeconds);

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table} WHERE status = %s AND completed_at < %s",
                JobStatus::Completed->value,
                $cutoff
            )
        );
    }

    public function cleanupFailed(int $olderThanSeconds = 604800): int
    {
        global $wpdb;

        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $olderThanSeconds);

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table}
                 WHERE status = %s AND attempts >= max_retries AND updated_at < %s",
                JobStatus::Failed->value,
                $cutoff
            )
        );
    }

    public function cancel(int $jobId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->table,
            ['status' => JobStatus::Cancelled->value],
            ['id' => $jobId, 'status' => JobStatus::Pending->value],
            ['%s'],
            ['%d', '%s']
        );
    }

    public function rescueStuck(int $timeoutSeconds = 300): int
    {
        global $wpdb;

        $cutoff = \gmdate('Y-m-d H:i:s', \time() - $timeoutSeconds);

        return (int) $wpdb->query(
            $wpdb->prepare(
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
        global $wpdb;

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
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
        global $wpdb;

        $groups = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT job_group FROM {$this->table} WHERE status IN (%s, %s)",
                JobStatus::Pending->value,
                JobStatus::Running->value
            )
        );

        if (!\is_array($groups)) {
            return [];
        }

        return $groups;
    }
}
