<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Factory;

use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;

class JobFactory
{
    private const MAX_KEY_LENGTH = 255;
    private const MAX_GROUP_LENGTH = 255;
    private const MAX_ERROR_LENGTH = 5000;

    /**
     * Create a new Job entity ready for enqueuing.
     *
     * @param string $key The job type key.
     * @param array<string, mixed> $payload The job payload.
     * @param string $group The queue group.
     * @param int $maxRetries Maximum retry attempts.
     * @param int $delay Seconds to delay execution.
     * @param int $intervalSeconds Recurrence interval (0 = one-off).
     */
    public function create(
        string $key,
        array $payload = [],
        string $group = 'default',
        int $maxRetries = 3,
        int $delay = 0,
        int $intervalSeconds = 0
    ): Job {
        if ($key === '') {
            throw new FrameworkException('Job key cannot be empty.');
        }

        if (\strlen($key) > self::MAX_KEY_LENGTH) {
            throw new FrameworkException(\sprintf('Job key cannot exceed %d characters.', self::MAX_KEY_LENGTH));
        }

        if (\strlen($group) > self::MAX_GROUP_LENGTH) {
            throw new FrameworkException(\sprintf('Job group cannot exceed %d characters.', self::MAX_GROUP_LENGTH));
        }

        if ($delay < 0) {
            $delay = 0;
        }

        if ($intervalSeconds < 0) {
            $intervalSeconds = 0;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $scheduledAt = $delay > 0
            ? $now->modify("+{$delay} seconds")
            : $now;

        $payloadJson = \json_encode($payload, \JSON_THROW_ON_ERROR);

        $job = new Job();
        $job->setKey($key)
            ->setGroup($group)
            ->setPayload($payload)
            ->setPayloadHash(\md5($payloadJson))
            ->setStatus(JobStatus::Pending)
            ->setAttempts(0)
            ->setMaxRetries($maxRetries)
            ->setScheduledAt($scheduledAt)
            ->setCreatedAt($now)
            ->setIntervalSeconds($intervalSeconds);

        return $job;
    }

    /**
     * Hydrate a Job entity from a database row.
     *
     * @param array<string, mixed> $row
     */
    public function fromRow(array $row): Job
    {
        $job = new Job();
        $job->setId((int) ($row['id'] ?? 0))
            ->setKey((string) ($row['job_key'] ?? ''))
            ->setGroup((string) ($row['job_group'] ?? 'default'))
            ->setPayload($this->decodePayload((string) ($row['payload'] ?? '[]')))
            ->setPayloadHash((string) ($row['payload_hash'] ?? ''))
            ->setStatus(JobStatus::tryFrom((string) ($row['status'] ?? '')) ?? JobStatus::Pending)
            ->setAttempts((int) ($row['attempts'] ?? 0))
            ->setMaxRetries((int) ($row['max_retries'] ?? 3))
            ->setLastError((string) ($row['last_error'] ?? ''))
            ->setClaimToken((string) ($row['claim_token'] ?? ''))
            ->setScheduledAt($this->parseDate((string) ($row['scheduled_at'] ?? '')))
            ->setClaimedAt($this->parseDate((string) ($row['claimed_at'] ?? '')))
            ->setCompletedAt($this->parseDate((string) ($row['completed_at'] ?? '')))
            ->setCreatedAt($this->parseDate((string) ($row['created_at'] ?? '')))
            ->setUpdatedAt($this->parseDate((string) ($row['updated_at'] ?? '')))
            ->setIntervalSeconds((int) ($row['interval_seconds'] ?? 0));

        return $job;
    }

    /**
     * Truncate an error message to a safe length.
     */
    public static function truncateError(string $message): string
    {
        if (\strlen($message) <= self::MAX_ERROR_LENGTH) {
            return $message;
        }

        return \substr($message, 0, self::MAX_ERROR_LENGTH - 12) . ' [truncated]';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $json): array
    {
        if ($json === '' || $json === '[]') {
            return [];
        }

        $decoded = \json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }

    private function parseDate(string $date): ?\DateTimeImmutable
    {
        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date, new \DateTimeZone('UTC'));

        return $parsed !== false ? $parsed : null;
    }
}
