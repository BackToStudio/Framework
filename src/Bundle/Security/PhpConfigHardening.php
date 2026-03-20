<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Checks and reports on PHP configuration settings that affect security.
 *
 * Evaluates php.ini directives and provides recommendations.
 * Does NOT modify php.ini — only audits and reports.
 *
 * Checked settings:
 * - expose_php: should be Off
 * - display_errors: should be Off in production
 * - allow_url_fopen: should be Off unless required
 * - allow_url_include: must be Off
 * - session.cookie_httponly: should be On
 * - session.cookie_secure: should be On
 * - session.use_strict_mode: should be On
 * - disable_functions: should include dangerous functions
 * - open_basedir: should be set
 */
class PhpConfigHardening implements SecurityRuleInterface
{
    /** @var array<string, array{expected: string, severity: string, description: string}> */
    private const CHECKS = [
        'expose_php' => [
            'expected' => '0',
            'severity' => 'warning',
            'description' => 'Reveals PHP version in response headers',
        ],
        'display_errors' => [
            'expected' => '0',
            'severity' => 'critical',
            'description' => 'Exposes error details and file paths to visitors',
        ],
        'display_startup_errors' => [
            'expected' => '0',
            'severity' => 'warning',
            'description' => 'Exposes PHP startup errors to visitors',
        ],
        'allow_url_fopen' => [
            'expected' => '0',
            'severity' => 'warning',
            'description' => 'Allows PHP to open remote URLs as files',
        ],
        'allow_url_include' => [
            'expected' => '0',
            'severity' => 'critical',
            'description' => 'Allows remote file inclusion attacks',
        ],
        'session.cookie_httponly' => [
            'expected' => '1',
            'severity' => 'warning',
            'description' => 'Session cookie accessible via JavaScript (XSS risk)',
        ],
        'session.cookie_secure' => [
            'expected' => '1',
            'severity' => 'warning',
            'description' => 'Session cookie sent over unencrypted connections',
        ],
        'session.use_strict_mode' => [
            'expected' => '1',
            'severity' => 'warning',
            'description' => 'Accepts uninitialized session IDs (session fixation risk)',
        ],
    ];

    /** @var string[] Functions that should ideally be in disable_functions */
    private const DANGEROUS_FUNCTIONS = [
        'exec',
        'passthru',
        'shell_exec',
        'system',
        'proc_open',
        'popen',
        'pcntl_exec',
        'eval',
    ];

    public function getName(): string
    {
        return 'php_config_hardening';
    }

    /**
     * Run all PHP configuration checks.
     *
     * @return array{issues: array<int, array{setting: string, current: string, expected: string, severity: string, description: string}>, score: int, total: int}
     */
    public function audit(): array
    {
        $issues = [];
        $total = count(self::CHECKS) + 2; // +2 for disable_functions and open_basedir

        foreach (self::CHECKS as $setting => $check) {
            $currentValue = $this->getIniValue($setting);

            if ($currentValue !== $check['expected']) {
                $issues[] = [
                    'setting' => $setting,
                    'current' => $currentValue,
                    'expected' => $check['expected'],
                    'severity' => $check['severity'],
                    'description' => $check['description'],
                ];
            }
        }

        // Check disable_functions
        $missingDisabled = $this->checkDisabledFunctions();

        if ($missingDisabled !== []) {
            $issues[] = [
                'setting' => 'disable_functions',
                'current' => 'Missing: ' . implode(', ', $missingDisabled),
                'expected' => 'Should disable dangerous functions',
                'severity' => 'warning',
                'description' => 'Dangerous functions are available to PHP scripts',
            ];
        }

        // Check open_basedir
        $openBasedir = $this->getIniValue('open_basedir');

        if ($openBasedir === '') {
            $issues[] = [
                'setting' => 'open_basedir',
                'current' => '(not set)',
                'expected' => 'Should be set to restrict file access',
                'severity' => 'warning',
                'description' => 'PHP can access any file on the server',
            ];
        }

        $passed = $total - count($issues);

        return [
            'issues' => $issues,
            'score' => $passed,
            'total' => $total,
        ];
    }

    /**
     * Get a summary status: secure, hardened, or needs_attention.
     */
    public function getStatus(): string
    {
        $result = $this->audit();

        if ($result['issues'] === []) {
            return 'secure';
        }

        $hasCritical = false;

        foreach ($result['issues'] as $issue) {
            if ($issue['severity'] === 'critical') {
                $hasCritical = true;

                break;
            }
        }

        return $hasCritical ? 'needs_attention' : 'hardened';
    }

    /**
     * Check which dangerous functions are NOT disabled.
     *
     * @return string[]
     */
    public function checkDisabledFunctions(): array
    {
        $disabled = $this->getDisabledFunctions();
        $missing = [];

        foreach (self::DANGEROUS_FUNCTIONS as $func) {
            if (! in_array($func, $disabled, true)) {
                $missing[] = $func;
            }
        }

        return $missing;
    }

    
    protected function getDisabledFunctions(): array
    {
        $disabled = $this->getIniValue('disable_functions');

        if ($disabled === '') {
            return [];
        }

        return array_map('trim', explode(',', $disabled));
    }

    protected function getIniValue(string $key): string
    {
        $value = ini_get($key);

        return $value !== false ? $value : '';
    }
}
