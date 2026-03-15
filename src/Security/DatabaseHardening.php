<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * Database security hardening for WordPress.
 *
 * - Detects unprepared SQL queries via the `query` filter (debug mode only)
 * - Monitors for dangerous SQL patterns (DROP, TRUNCATE, ALTER)
 * - Validates that $wpdb->prepare() is used for parameterized queries
 */
class DatabaseHardening implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;
    private LoggerInterface $logger;
    private bool $debugMode;

    /** @var string[] Dangerous SQL patterns that should never appear in normal queries */
    private const DANGEROUS_PATTERNS = [
        '/\bDROP\s+TABLE\b/i',
        '/\bTRUNCATE\s+TABLE\b/i',
        '/\bALTER\s+TABLE\b/i',
        '/\bLOAD_FILE\s*\(/i',
        '/\bINTO\s+OUTFILE\b/i',
        '/\bINTO\s+DUMPFILE\b/i',
        '/\bUNION\s+SELECT\b/i',
        '/\bSLEEP\s*\(/i',
        '/\bBENCHMARK\s*\(/i',
    ];

    /** @var string[] Common WP patterns that legitimately use these SQL constructs */
    private const SAFE_CALLERS = [
        'wp-admin/includes/upgrade.php',
        'wp-admin/includes/schema.php',
        'wp-includes/wp-db.php',
    ];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
        bool $debugMode = false,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
        $this->debugMode = $debugMode;
    }

    public function getName(): string
    {
        return 'database_hardening';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('query', [$this, 'inspectQuery']);
    }

    /**
     * Inspect a SQL query for dangerous patterns.
     *
     * In debug mode, also detects potentially unprepared queries.
     */
    public function inspectQuery(string $query): string
    {
        $dangerousPatterns = $this->detectDangerousPatterns($query);

        if ($dangerousPatterns !== []) {
            if (! $this->isFromSafeCaller()) {
                $this->logger->warning('Dangerous SQL pattern detected', [
                    'query' => $this->truncateQuery($query),
                    'patterns' => $dangerousPatterns,
                ]);
            }
        }

        if ($this->debugMode) {
            $this->detectUnpreparedQuery($query);
        }

        return $query;
    }

    /**
     * Detect dangerous SQL patterns in a query.
     *
     * @return string[]
     */
    public function detectDangerousPatterns(string $query): array
    {
        $matches = [];

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $query) === 1) {
                $matches[] = $pattern;
            }
        }

        return $matches;
    }

    /**
     * Detect a potentially unprepared query (debug mode only).
     *
     * Looks for string interpolation patterns that suggest the query
     * was not properly prepared via $wpdb->prepare().
     */
    public function detectUnpreparedQuery(string $query): void
    {
        // Skip queries from WP core
        if ($this->isFromSafeCaller()) {
            return;
        }

        // Already prepared queries use %s, %d, %f placeholders
        if (preg_match('/%[sdf]/', $query) === 1) {
            return;
        }

        // Detect string values that look like they were directly interpolated
        if (preg_match("/WHERE\s+\w+\s*=\s*'[^']+'/i", $query) === 1) {
            $this->logger->info('Potentially unprepared SQL query detected', [
                'query' => $this->truncateQuery($query),
                'hint' => 'Consider using $wpdb->prepare() for parameterized queries',
            ]);
        }
    }

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    protected function isFromSafeCaller(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            foreach (self::SAFE_CALLERS as $safeCaller) {
                if (str_contains($file, $safeCaller)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function truncateQuery(string $query): string
    {
        $query = trim($query);

        if (strlen($query) <= 200) {
            return $query;
        }

        return substr($query, 0, 200) . '...';
    }
}
