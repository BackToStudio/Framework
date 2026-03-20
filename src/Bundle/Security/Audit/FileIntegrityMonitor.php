<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Audit;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use BackTo\Framework\Bundle\Security\Contracts\FileIntegrityRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Monitors critical WordPress files for unauthorized modifications.
 *
 * Creates a SHA-256 baseline of core files and detects changes.
 * Critical files: wp-config.php, .htaccess, wp-settings.php, wp-includes core.
 */
class FileIntegrityMonitor implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly FileIntegrityRepositoryInterface $repository;
    private readonly LoggerInterface $logger;
    private readonly CronSchedulerInterface $cronScheduler;

    /** @var string[] Relative paths from ABSPATH to monitor */
    private const CRITICAL_FILES = [
        'wp-config.php',
        '.htaccess',
        'wp-settings.php',
        'wp-login.php',
        'wp-load.php',
        'wp-blog-header.php',
        'index.php',
        'wp-cron.php',
        'xmlrpc.php',
    ];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        FileIntegrityRepositoryInterface $repository,
        LoggerInterface $logger,
        CronSchedulerInterface $cronScheduler,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->cronScheduler = $cronScheduler;
    }

    public function getName(): string
    {
        return 'file_integrity_monitor';
    }

    private const CRON_HOOK = 'backto_file_integrity_check';

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_init', [$this, 'ensureScheduled']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'scheduleCheck']);
    }

    /**
     * Ensure the cron event is scheduled (lightweight check on admin_init).
     */
    public function ensureScheduled(): void
    {
        if (!$this->cronScheduler->isScheduled(self::CRON_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CRON_HOOK, 'hourly');
        }
    }

    public function scheduleCheck(): void
    {
        if (! $this->repository->hasBaseline()) {
            $this->createBaseline();

            return;
        }

        $result = $this->check();

        if ($result['modified'] !== [] || $result['missing'] !== [] || $result['added'] !== []) {
            $this->logger->warning('File integrity check failed', $result);
        }
    }

    public function createBaseline(): void
    {
        $basePath = $this->getBasePath();
        $hashes = $this->hashFiles($basePath, self::CRITICAL_FILES);

        $this->repository->storeBaseline($hashes);

        $this->logger->info('File integrity baseline created', [
            'file_count' => count($hashes),
        ]);
    }

    /**
     * Compare current file hashes against baseline.
     *
     * @return array{modified: string[], missing: string[], added: string[]}
     */
    public function check(): array
    {
        $baseline = $this->repository->getBaseline();

        if ($baseline === null) {
            return ['modified' => [], 'missing' => [], 'added' => []];
        }

        $basePath = $this->getBasePath();
        $currentHashes = $this->hashFiles($basePath, self::CRITICAL_FILES);

        $modified = [];
        $missing = [];
        $added = [];

        // Check for modified and missing files
        foreach ($baseline as $file => $expectedHash) {
            if (! isset($currentHashes[$file])) {
                $missing[] = $file;
            } elseif ($currentHashes[$file] !== $expectedHash) {
                $modified[] = $file;
            }
        }

        // Check for new files
        foreach ($currentHashes as $file => $hash) {
            if (! isset($baseline[$file])) {
                $added[] = $file;
            }
        }

        return [
            'modified' => $modified,
            'missing' => $missing,
            'added' => $added,
        ];
    }

    /**
     * @param string[] $files
     * @return array<string, string>
     */
    public function hashFiles(string $basePath, array $files): array
    {
        $hashes = [];

        foreach ($files as $file) {
            $fullPath = $basePath . '/' . $file;

            if (! $this->fileExists($fullPath)) {
                continue;
            }

            $hash = $this->hashFile($fullPath);

            if ($hash !== null) {
                $hashes[$file] = $hash;
            } else {
                $this->logger->warning('Failed to hash file during integrity check', ['file' => $file]);
            }
        }

        return $hashes;
    }

    protected function getBasePath(): string
    {
        if (defined('ABSPATH')) {
            return rtrim(ABSPATH, '/');
        }

        return '';
    }

    protected function fileExists(string $path): bool
    {
        // Reject symlinks to prevent directory traversal attacks
        if (is_link($path)) {
            $this->logger->info('Skipping symlink during integrity check', ['path' => $path]);

            return false;
        }

        return is_file($path) && is_readable($path);
    }

    protected function hashFile(string $path): ?string
    {
        $hash = hash_file('sha256', $path);

        return $hash !== false ? $hash : null;
    }
}
