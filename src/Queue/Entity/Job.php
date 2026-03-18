<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Entity;

/**
 * Represents a queued job instance with its state and metadata.
 */
final class Job
{
    private int $id = 0;
    private string $key = '';
    private string $group = 'default';

    /** @var array<string, mixed> */
    private array $payload = [];
    private string $payloadHash = '';
    private JobStatus $status = JobStatus::Pending;
    private int $attempts = 0;
    private int $maxRetries = 3;
    private string $lastError = '';
    private string $claimToken = '';
    private ?\DateTimeImmutable $scheduledAt = null;
    private ?\DateTimeImmutable $claimedAt = null;
    private ?\DateTimeImmutable $completedAt = null;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;
    private int $intervalSeconds = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getPayloadHash(): string
    {
        return $this->payloadHash;
    }

    public function setPayloadHash(string $payloadHash): self
    {
        $this->payloadHash = $payloadHash;

        return $this;
    }

    public function getStatus(): JobStatus
    {
        return $this->status;
    }

    public function setStatus(JobStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function setLastError(string $lastError): self
    {
        $this->lastError = $lastError;

        return $this;
    }

    public function getClaimToken(): string
    {
        return $this->claimToken;
    }

    public function setClaimToken(string $claimToken): self
    {
        $this->claimToken = $claimToken;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function setClaimedAt(?\DateTimeImmutable $claimedAt): self
    {
        $this->claimedAt = $claimedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIntervalSeconds(): int
    {
        return $this->intervalSeconds;
    }

    public function setIntervalSeconds(int $intervalSeconds): self
    {
        $this->intervalSeconds = $intervalSeconds;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->intervalSeconds > 0;
    }

    public function isReady(): bool
    {
        if ($this->status !== JobStatus::Pending) {
            return false;
        }

        if ($this->scheduledAt === null) {
            return true;
        }

        return $this->scheduledAt <= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->maxRetries;
    }
}
