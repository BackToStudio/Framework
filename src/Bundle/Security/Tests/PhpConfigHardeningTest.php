<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Hardening\PhpConfigHardening;
use PHPUnit\Framework\TestCase;

class TestablePhpConfigHardening extends PhpConfigHardening
{
    /** @var array<string, string> */
    private array $iniValues = [];

    private string $disabledFunctions = '';

    /**
     * @param array<string, string> $iniValues
     */
    public function setIniValues(array $iniValues): void
    {
        $this->iniValues = $iniValues;
    }

    public function setDisabledFunctionsString(string $functions): void
    {
        $this->disabledFunctions = $functions;
    }

    protected function getIniValue(string $key): string
    {
        if ($key === 'disable_functions') {
            return $this->disabledFunctions;
        }

        return $this->iniValues[$key] ?? '';
    }
}

class PhpConfigHardeningTest extends TestCase
{
    private TestablePhpConfigHardening $hardening;

    protected function setUp(): void
    {
        $this->hardening = new TestablePhpConfigHardening();
    }

    public function testImplementsSecurityRuleInterface(): void
    {
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->hardening);
    }

    public function testGetName(): void
    {
        $this->assertSame('php_config_hardening', $this->hardening->getName());
    }

    public function testAuditReturnsNoIssuesWhenAllSecure(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $result = $this->hardening->audit();

        $this->assertSame([], $result['issues']);
        $this->assertSame($result['total'], $result['score']);
    }

    public function testAuditDetectsExposedPhp(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '1',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $result = $this->hardening->audit();

        $this->assertCount(1, $result['issues']);
        $this->assertSame('expose_php', $result['issues'][0]['setting']);
        $this->assertSame('warning', $result['issues'][0]['severity']);
    }

    public function testAuditDetectsDisplayErrors(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '1',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $result = $this->hardening->audit();

        $this->assertCount(1, $result['issues']);
        $this->assertSame('display_errors', $result['issues'][0]['setting']);
        $this->assertSame('critical', $result['issues'][0]['severity']);
    }

    public function testAuditDetectsMissingDisabledFunctions(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru');

        $result = $this->hardening->audit();

        $this->assertCount(1, $result['issues']);
        $this->assertSame('disable_functions', $result['issues'][0]['setting']);
        $this->assertStringContainsString('shell_exec', $result['issues'][0]['current']);
    }

    public function testAuditDetectsEmptyDisabledFunctions(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('');

        $result = $this->hardening->audit();

        $this->assertCount(1, $result['issues']);
        $this->assertSame('disable_functions', $result['issues'][0]['setting']);
    }

    public function testAuditDetectsMissingOpenBasedir(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $result = $this->hardening->audit();

        $this->assertCount(1, $result['issues']);
        $this->assertSame('open_basedir', $result['issues'][0]['setting']);
    }

    public function testAuditScoreCalculation(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '1',
            'display_errors' => '1',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $result = $this->hardening->audit();

        $this->assertSame(10, $result['total']); // 8 checks + disable_functions + open_basedir
        $this->assertSame(8, $result['score']); // 10 - 2 issues
        $this->assertCount(2, $result['issues']);
    }

    public function testGetStatusReturnsSecureWhenNoIssues(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $this->assertSame('secure', $this->hardening->getStatus());
    }

    public function testGetStatusReturnsNeedsAttentionForCriticalIssues(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '0',
            'display_errors' => '1',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $this->assertSame('needs_attention', $this->hardening->getStatus());
    }

    public function testGetStatusReturnsHardenedForWarningsOnly(): void
    {
        $this->hardening->setIniValues([
            'expose_php' => '1',
            'display_errors' => '0',
            'display_startup_errors' => '0',
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'open_basedir' => '/var/www',
        ]);
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $this->assertSame('hardened', $this->hardening->getStatus());
    }

    public function testCheckDisabledFunctionsReturnsMissingOnes(): void
    {
        $this->hardening->setDisabledFunctionsString('exec,system');

        $missing = $this->hardening->checkDisabledFunctions();

        $this->assertContains('passthru', $missing);
        $this->assertContains('shell_exec', $missing);
        $this->assertNotContains('exec', $missing);
        $this->assertNotContains('system', $missing);
    }

    public function testCheckDisabledFunctionsReturnsAllWhenNoneDisabled(): void
    {
        $this->hardening->setDisabledFunctionsString('');

        $missing = $this->hardening->checkDisabledFunctions();

        $this->assertCount(8, $missing);
    }

    public function testCheckDisabledFunctionsReturnsEmptyWhenAllDisabled(): void
    {
        $this->hardening->setDisabledFunctionsString('exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,eval');

        $missing = $this->hardening->checkDisabledFunctions();

        $this->assertSame([], $missing);
    }
}
