<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\HealthCheck;

use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckResult;
use BackTo\Framework\Bundle\Security\SecurityRuleRegistry;

/**
 * Verifies that security rules are active and critical settings are properly configured.
 */
class SecurityHealthCheck implements HealthCheckInterface
{
    private readonly SecurityRuleRegistry $ruleRegistry;

    /** @var string[] */
    private const CRITICAL_RULES = [
        'http_headers_hardening',
        'disable_xmlrpc',
        'hide_wordpress_version',
        'login_hardening',
        'upload_security',
        'capability_hardening',
        'security_audit_logger',
        'disable_file_editor',
    ];

    public function __construct(SecurityRuleRegistry $ruleRegistry)
    {
        $this->ruleRegistry = $ruleRegistry;
    }

    public function getName(): string
    {
        return 'security';
    }

    public function check(): HealthCheckResult
    {
        $issues = [];
        $metadata = [];

        $activeRules = $this->ruleRegistry->getActiveRuleNames();
        $metadata['active_rules'] = $activeRules;
        $metadata['active_rules_count'] = count($activeRules);

        $missingCritical = $this->checkCriticalRules($activeRules);
        if ($missingCritical !== []) {
            $issues[] = 'Missing critical security rules: ' . implode(', ', $missingCritical);
            $metadata['missing_critical_rules'] = $missingCritical;
        }

        if ($this->isFileEditorEnabled()) {
            $issues[] = 'File editor is enabled (DISALLOW_FILE_EDIT not set)';
            $metadata['file_editor_enabled'] = true;
        }

        $phpVersion = $this->getPhpVersion();
        $metadata['php_version'] = $phpVersion;

        if (!$this->isPhpVersionSupported($phpVersion)) {
            $issues[] = sprintf('PHP %s is no longer actively supported', $phpVersion);
        }

        if ($issues === []) {
            return HealthCheckResult::healthy('All security checks passed', $metadata);
        }

        if ($missingCritical !== []) {
            return HealthCheckResult::unhealthy(implode('; ', $issues), $metadata);
        }

        return HealthCheckResult::degraded(implode('; ', $issues), $metadata);
    }

    
    private function checkCriticalRules(array $activeRules): array
    {
        $missing = [];

        foreach (self::CRITICAL_RULES as $rule) {
            if (!in_array($rule, $activeRules, true)) {
                $missing[] = $rule;
            }
        }

        return $missing;
    }

    protected function isFileEditorEnabled(): bool
    {
        return !defined('DISALLOW_FILE_EDIT') || DISALLOW_FILE_EDIT !== true;
    }

    protected function getPhpVersion(): string
    {
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }

    protected function isPhpVersionSupported(string $version): bool
    {
        return version_compare($version, '8.2', '>=');
    }
}
