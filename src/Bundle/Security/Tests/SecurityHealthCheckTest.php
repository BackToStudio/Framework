<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\HealthCheck\SecurityHealthCheck;
use BackTo\Framework\Bundle\Security\SecurityRuleRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control environment checks.
 */
class TestableSecurityHealthCheck extends SecurityHealthCheck
{
    private bool $fileEditorEnabled = false;
    private string $phpVersion = '8.4';

    public function setFileEditorEnabled(bool $enabled): void
    {
        $this->fileEditorEnabled = $enabled;
    }

    public function setPhpVersion(string $version): void
    {
        $this->phpVersion = $version;
    }

    protected function isFileEditorEnabled(): bool
    {
        return $this->fileEditorEnabled;
    }

    protected function getPhpVersion(): string
    {
        return $this->phpVersion;
    }
}

class SecurityHealthCheckTest extends TestCase
{
    private SecurityRuleRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new SecurityRuleRegistry();
    }

    public function testImplementsHealthCheckInterface(): void
    {
        $check = new SecurityHealthCheck($this->registry);
        $this->assertInstanceOf(HealthCheckInterface::class, $check);
    }

    public function testGetName(): void
    {
        $check = new SecurityHealthCheck($this->registry);
        $this->assertSame('security', $check->getName());
    }

    public function testHealthyWhenAllCriticalRulesActive(): void
    {
        $this->addCriticalRules();

        $check = new TestableSecurityHealthCheck($this->registry);
        $result = $check->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame('All security checks passed', $result->getMessage());
    }

    public function testUnhealthyWhenCriticalRulesMissing(): void
    {
        $check = new TestableSecurityHealthCheck($this->registry);
        $result = $check->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(HealthCheckResult::STATUS_UNHEALTHY, $result->getStatus());
        $this->assertStringContainsString('Missing critical security rules', $result->getMessage());
    }

    public function testDegradedWhenFileEditorEnabled(): void
    {
        $this->addCriticalRules();

        $check = new TestableSecurityHealthCheck($this->registry);
        $check->setFileEditorEnabled(true);
        $result = $check->check();

        $this->assertSame(HealthCheckResult::STATUS_DEGRADED, $result->getStatus());
        $this->assertStringContainsString('File editor is enabled', $result->getMessage());
    }

    public function testDegradedWhenOldPhpVersion(): void
    {
        $this->addCriticalRules();

        $check = new TestableSecurityHealthCheck($this->registry);
        $check->setPhpVersion('8.1');
        $result = $check->check();

        $this->assertSame(HealthCheckResult::STATUS_DEGRADED, $result->getStatus());
        $this->assertStringContainsString('no longer actively supported', $result->getMessage());
    }

    public function testMetadataContainsActiveRules(): void
    {
        $this->addCriticalRules();

        $check = new TestableSecurityHealthCheck($this->registry);
        $result = $check->check();
        $metadata = $result->getMetadata();

        $this->assertArrayHasKey('active_rules', $metadata);
        $this->assertArrayHasKey('active_rules_count', $metadata);
        $this->assertSame(8, $metadata['active_rules_count']);
    }

    public function testMetadataContainsPhpVersion(): void
    {
        $this->addCriticalRules();

        $check = new TestableSecurityHealthCheck($this->registry);
        $check->setPhpVersion('8.3');
        $result = $check->check();

        $this->assertSame('8.3', $result->getMetadata()['php_version']);
    }

    public function testMultipleIssuesCombined(): void
    {
        // No critical rules + file editor enabled + old PHP
        $check = new TestableSecurityHealthCheck($this->registry);
        $check->setFileEditorEnabled(true);
        $check->setPhpVersion('8.1');
        $result = $check->check();

        $this->assertSame(HealthCheckResult::STATUS_UNHEALTHY, $result->getStatus());
        $this->assertStringContainsString('Missing critical', $result->getMessage());
        $this->assertStringContainsString('File editor', $result->getMessage());
        $this->assertStringContainsString('no longer actively supported', $result->getMessage());
    }

    private function addCriticalRules(): void
    {
        $criticalNames = [
            'http_headers_hardening',
            'disable_xmlrpc',
            'hide_wordpress_version',
            'login_hardening',
            'upload_security',
            'capability_hardening',
            'security_audit_logger',
            'disable_file_editor',
        ];

        foreach ($criticalNames as $name) {
            $rule = $this->createMock(SecurityRuleInterface::class);
            $rule->method('getName')->willReturn($name);
            $this->registry->add($rule);
        }
    }
}
