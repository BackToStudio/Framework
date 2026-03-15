<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Value object representing the result of a health check.
 */
class HealthCheckResult
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';

    private string $status;
    private string $message;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    private function __construct(string $status, string $message = '', array $metadata = [])
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
        return new self(self::STATUS_HEALTHY, $message, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function degraded(string $message, array $metadata = []): self
    {
        return new self(self::STATUS_DEGRADED, $message, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function unhealthy(string $message, array $metadata = []): self
    {
        return new self(self::STATUS_UNHEALTHY, $message, $metadata);
    }

    public function getStatus(): string
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
        return $this->status === self::STATUS_HEALTHY;
    }

    /**
     * @return array{status: string, message: string, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
