<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Value object representing the result of a health check.
 */
final class HealthCheckResult
{
    /** @deprecated Use HealthCheckStatus::Healthy instead */
    public const STATUS_HEALTHY = 'healthy';
    /** @deprecated Use HealthCheckStatus::Degraded instead */
    public const STATUS_DEGRADED = 'degraded';
    /** @deprecated Use HealthCheckStatus::Unhealthy instead */
    public const STATUS_UNHEALTHY = 'unhealthy';

    private readonly HealthCheckStatus $status;
    private readonly string $message;
    /** @var array<string, mixed> */
    private readonly array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    private function __construct(HealthCheckStatus $status, string $message = '', array $metadata = [])
    {
        $this->status = $status;
        $this->message = $message;
        $this->metadata = $metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function healthy(string $message = '', array $metadata = []): self
    {
        return new self(HealthCheckStatus::Healthy, $message, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function degraded(string $message, array $metadata = []): self
    {
        return new self(HealthCheckStatus::Degraded, $message, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function unhealthy(string $message, array $metadata = []): self
    {
        return new self(HealthCheckStatus::Unhealthy, $message, $metadata);
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getHealthCheckStatus(): HealthCheckStatus
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isHealthy(): bool
    {
        return $this->status === HealthCheckStatus::Healthy;
    }

    /**
     * @return array{status: string, message: string, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
