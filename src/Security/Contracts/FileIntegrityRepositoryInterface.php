<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for persisting file integrity baselines.
 */
interface FileIntegrityRepositoryInterface
{
    /**
     * @param array<string, string> $hashes File path => SHA-256 hash
     */
    public function storeBaseline(array $hashes): void;

    /**
     * @return array<string, string>|null Null if no baseline exists
     */
    public function getBaseline(): ?array;

    public function hasBaseline(): bool;

    public function getBaselineTimestamp(): ?int;
}
