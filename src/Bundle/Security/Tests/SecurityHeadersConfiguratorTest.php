<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Headers\SecurityHeadersConfigurator;
use PHPUnit\Framework\TestCase;

class TestableSecurityHeadersConfigurator extends SecurityHeadersConfigurator
{
    /** @var array<string, string> */
    public array $sentHeaders = [];
}

class SecurityHeadersConfiguratorTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private ResponseEmitterInterface $responseEmitter;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $this->responseEmitter->method('headersSent')->willReturn(false);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $this->assertInstanceOf(Hooks::class, $configurator);
        $this->assertInstanceOf(SecurityRuleInterface::class, $configurator);
    }

    public function testGetName(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $this->assertSame('security_headers_configurator', $configurator->getName());
    }

    public function testHooksRegistersSendHeadersActions(): void
    {
        $this->dispatcher->expects($this->exactly(2))->method('addAction');

        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->hooks();
    }

    public function testProductionPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'production');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
        $this->assertStringContainsString('preload', $headers['Strict-Transport-Security']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertStringContainsString('payment=()', $headers['Permissions-Policy']);
    }

    public function testStagingPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'staging');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=86400', $headers['Strict-Transport-Security']);
        $this->assertStringNotContainsString('preload', $headers['Strict-Transport-Security']);
    }

    public function testDevelopmentPreset(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'development');
        $headers = $configurator->getHeaders();

        $this->assertArrayNotHasKey('Strict-Transport-Security', $headers);
        $this->assertSame('no-referrer-when-downgrade', $headers['Referrer-Policy']);
    }

    public function testUnknownEnvironmentFallsToProduction(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'unknown');
        $headers = $configurator->getHeaders();

        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
    }

    public function testGetEnvironment(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'staging');
        $this->assertSame('staging', $configurator->getEnvironment());
    }

    public function testSetHeader(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setHeader('X-Custom', 'value');

        $this->assertSame('value', $configurator->getHeaders()['X-Custom']);
    }

    public function testRemoveHeader(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter, 'production');
        $configurator->removeHeader('Strict-Transport-Security');

        $this->assertArrayNotHasKey('Strict-Transport-Security', $configurator->getHeaders());
    }

    public function testSetHstsMaxAge(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setHstsMaxAge(7200, true, false);

        $this->assertSame(
            'max-age=7200; includeSubDomains',
            $configurator->getHeaders()['Strict-Transport-Security']
        );
    }

    public function testSetHstsMaxAgeWithPreload(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setHstsMaxAge(31536000, true, true);

        $hsts = $configurator->getHeaders()['Strict-Transport-Security'];
        $this->assertStringContainsString('preload', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    public function testSetHstsMaxAgeWithoutSubDomains(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setHstsMaxAge(3600, false, false);

        $this->assertSame(
            'max-age=3600',
            $configurator->getHeaders()['Strict-Transport-Security']
        );
    }

    public function testSetFrameOptions(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setFrameOptions('DENY');

        $this->assertSame('DENY', $configurator->getHeaders()['X-Frame-Options']);
    }

    public function testSetReferrerPolicy(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
        $configurator->setReferrerPolicy('no-referrer');

        $this->assertSame('no-referrer', $configurator->getHeaders()['Referrer-Policy']);
    }

    public function testSetPermissionsPolicy(): void
    {
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
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
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $responseEmitter->method('headersSent')->willReturn(false);

        $sentHeaders = [];
        $responseEmitter->method('sendHeader')
            ->willReturnCallback(function (string $header) use (&$sentHeaders) {
                $sentHeaders[] = $header;
            });

        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $responseEmitter, 'production');
        $configurator->sendHeaders();

        $headerString = implode("\n", $sentHeaders);
        $this->assertNotEmpty($sentHeaders);
        $this->assertStringContainsString('Strict-Transport-Security', $headerString);
        $this->assertStringContainsString('X-Content-Type-Options', $headerString);
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
        $configurator = new TestableSecurityHeadersConfigurator($this->dispatcher, $this->responseEmitter);
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
