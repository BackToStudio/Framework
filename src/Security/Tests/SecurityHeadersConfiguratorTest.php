<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\SecurityHeadersConfigurator;
use PHPUnit\Framework\TestCase;

class TestableSecurityHeadersConfigurator extends SecurityHeadersConfigurator
{
    /** @var array<string, string> */
    public array $sentHeaders = [];

    protected function headersSent(): bool
    {
        return false;
    }

    public function sendHeaders(): void
    {
        $this->sentHeaders = $this->getHeaders();
    }

    protected function removeServerHeaders(): void
    {
        // No-op in tests
    }
}

class SecurityHeadersConfiguratorTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $this->assertInstanceOf(Hooks::class, $configurator);
        $this->assertInstanceOf(SecurityRuleInterface::class, $configurator);
    }

    public function testGetName(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $this->assertSame('security_headers_configurator', $configurator->getName());
    }

    public function testHooksRegistersSendHeadersActions(): void
    {
        $this->dispatcher->expects($this->exactly(2))->method('addAction');

        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->hooks();
    }

    public function testProductionPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'production');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
        $this->assertStringContainsString('preload', $headers['Strict-Transport-Security']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertStringContainsString('payment=()', $headers['Permissions-Policy']);
    }

    public function testStagingPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'staging');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=86400', $headers['Strict-Transport-Security']);
        $this->assertStringNotContainsString('preload', $headers['Strict-Transport-Security']);
    }

    public function testDevelopmentPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'development');
        $headers = $configurator->getHeaders();

        $this->assertArrayNotHasKey('Strict-Transport-Security', $headers);
        $this->assertSame('no-referrer-when-downgrade', $headers['Referrer-Policy']);
    }

    public function testUnknownEnvironmentFallsToProduction(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'unknown');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
    }

    public function testGetEnvironment(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'staging');
        $this->assertSame('staging', $configurator->getEnvironment());
    }

    public function testSetHeader(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setHeader('X-Custom', 'value');

        $this->assertSame('value', $configurator->getHeaders()['X-Custom']);
    }

    public function testRemoveHeader(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'production');
        $configurator->removeHeader('Strict-Transport-Security');

        $this->assertArrayNotHasKey('Strict-Transport-Security', $configurator->getHeaders());
    }

    public function testSetHstsMaxAge(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setHstsMaxAge(7200, true, false);

        $this->assertSame(
            'max-age=7200; includeSubDomains',
            $configurator->getHeaders()['Strict-Transport-Security']
        );
    }

    public function testSetHstsMaxAgeWithPreload(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setHstsMaxAge(31536000, true, true);

        $hsts = $configurator->getHeaders()['Strict-Transport-Security'];
        $this->assertStringContainsString('preload', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    public function testSetHstsMaxAgeWithoutSubDomains(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setHstsMaxAge(3600, false, false);

        $this->assertSame(
            'max-age=3600',
            $configurator->getHeaders()['Strict-Transport-Security']
        );
    }

    public function testSetFrameOptions(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setFrameOptions('DENY');

        $this->assertSame('DENY', $configurator->getHeaders()['X-Frame-Options']);
    }

    public function testSetReferrerPolicy(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setReferrerPolicy('no-referrer');

        $this->assertSame('no-referrer', $configurator->getHeaders()['Referrer-Policy']);
    }

    public function testSetPermissionsPolicy(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $configurator->setPermissionsPolicy([
            'camera' => [],
            'microphone' => [],
            'geolocation' => ['self'],
        ]);

        $policy = $configurator->getHeaders()['Permissions-Policy'];
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=(self)', $policy);
    }

    public function testSendHeadersSetsAllConfiguredHeaders(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, 'production');
        $configurator->sendHeaders();

        $this->assertNotEmpty($configurator->sentHeaders);
        $this->assertArrayHasKey('Strict-Transport-Security', $configurator->sentHeaders);
        $this->assertArrayHasKey('X-Content-Type-Options', $configurator->sentHeaders);
    }

    public function testGetAvailablePresets(): void
    {
        $presets = SecurityHeadersConfigurator::getAvailablePresets();

        $this->assertArrayHasKey('production', $presets);
        $this->assertArrayHasKey('staging', $presets);
        $this->assertArrayHasKey('development', $presets);
    }

    public function testFluentInterface(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher);
        $result = $configurator
            ->setHeader('X-Custom', 'value')
            ->removeHeader('X-Frame-Options')
            ->setHstsMaxAge(3600)
            ->setFrameOptions('DENY')
            ->setReferrerPolicy('no-referrer')
            ->setPermissionsPolicy(['camera' => []]);

        $this->assertSame($configurator, $result);
    }
}
