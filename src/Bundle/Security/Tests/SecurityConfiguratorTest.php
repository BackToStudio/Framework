<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\Bundle\Security\SecurityConfigurator;
use PHPUnit\Framework\TestCase;

class SecurityConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new SecurityConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new SecurityConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new SecurityConfigurator();

        $this->assertSame($configurator, $configurator->headersEnabled(true));
        $this->assertSame($configurator, $configurator->xmlrpcDisabled(true));
        $this->assertSame($configurator, $configurator->hideVersion(true));
        $this->assertSame($configurator, $configurator->cspReportOnly(false));
        $this->assertSame($configurator, $configurator->passwordMinLength(12));
        $this->assertSame($configurator, $configurator->maxConcurrentSessions(1));
        $this->assertSame($configurator, $configurator->restApiRequireAuth(true));
        $this->assertSame($configurator, $configurator->disableFileEditor(true));
        $this->assertSame($configurator, $configurator->twoFactorEnabled(false));
        $this->assertSame($configurator, $configurator->twoFactorIssuer('Test'));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new SecurityConfigurator();
        $configurator->passwordMinLength(16);

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame(16, $params['security.password_min_length']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new SecurityConfigurator())
            ->headersEnabled(false)
            ->xmlrpcDisabled(false)
            ->hideVersion(false)
            ->cspReportOnly(true)
            ->passwordMinLength(20)
            ->maxConcurrentSessions(3)
            ->restApiRequireAuth(false)
            ->disableFileEditor(false)
            ->twoFactorEnabled(true)
            ->twoFactorIssuer('MonApp');

        $params = $configurator->toParameters();

        $this->assertSame(false, $params['security.headers_enabled']);
        $this->assertSame(false, $params['security.xmlrpc_disabled']);
        $this->assertSame(false, $params['security.hide_version']);
        $this->assertSame(true, $params['security.csp_report_only']);
        $this->assertSame(20, $params['security.password_min_length']);
        $this->assertSame(3, $params['security.max_concurrent_sessions']);
        $this->assertSame(false, $params['security.rest_api_require_auth']);
        $this->assertSame(false, $params['security.disable_file_editor']);
        $this->assertSame(true, $params['security.two_factor_enabled']);
        $this->assertSame('MonApp', $params['security.two_factor_issuer']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new SecurityConfigurator())
            ->passwordMinLength(10)
            ->passwordMinLength(20);

        $this->assertSame(20, $configurator->toParameters()['security.password_min_length']);
    }
}
