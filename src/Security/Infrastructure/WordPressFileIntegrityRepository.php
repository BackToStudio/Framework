<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\FileIntegrityRepositoryInterface;

use function get_option;
use function update_option;

/**
 * WordPress adapter for file integrity baseline storage using options API.
 */
final class WordPressFileIntegrityRepository implements FileIntegrityRepositoryInterface
{
    private const BASELINE_OPTION = 'backto_file_integrity_baseline';
    private const TIMESTAMP_OPTION = 'backto_file_integrity_timestamp';

    /**
     * @param array<string, string> $hashes
     */
    public function storeBaseline(array $hashes): void
    {
        update_option(self::BASELINE_OPTION, $hashes, false);
        update_option(self::TIMESTAMP_OPTION, time(), false);
    }

    /**
     * @return array<string, string>|null
     */
    public function getBaseline(): ?array
    {
        $baseline = get_option(self::BASELINE_OPTION, null);

        if (! is_array($baseline)) {
            return null;
        }

        return $baseline;
    }

    public function hasBaseline(): bool
    {
        return $this->getBaseline() !== null;
    }

    public function getBaselineTimestamp(): ?int
    {
        $timestamp = get_option(self::TIMESTAMP_OPTION, null);

        return $timestamp !== null ? (int) $timestamp : null;
    }
}
