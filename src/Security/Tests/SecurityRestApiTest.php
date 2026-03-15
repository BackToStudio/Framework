<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\RestApi\SecurityAuditLogRoute;
use BackTo\Framework\Security\RestApi\SecurityHealthRoute;
use BackTo\Framework\Security\RestApi\SecurityScanRoute;
use BackTo\Framework\Security\FileIntegrityMonitor;
use BackTo\Framework\Security\HealthCheck\SecurityHealthCheck;
use BackTo\Framework\Security\MalwareScanner;
use PHPUnit\Framework\TestCase;

class SecurityRestApiTest extends TestCase
{
    public function testAuditLogRouteImplementsRestRouteInterface(): void
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $route = new SecurityAuditLogRoute($repository);

        $this->assertInstanceOf(RestRouteInterface::class, $route);
    }

    public function testAuditLogRouteConfig(): void
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $route = new SecurityAuditLogRoute($repository);

        $this->assertSame('backto/v1', $route->getNamespace());
        $this->assertSame('/security/audit-log', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertNotNull($route->getPermissionCallback());
    }

    public function testHealthRouteImplementsRestRouteInterface(): void
    {
        $healthCheck = $this->createMock(SecurityHealthCheck::class);
        $route = new SecurityHealthRoute($healthCheck);

        $this->assertInstanceOf(RestRouteInterface::class, $route);
    }

    public function testHealthRouteConfig(): void
    {
        $healthCheck = $this->createMock(SecurityHealthCheck::class);
        $route = new SecurityHealthRoute($healthCheck);

        $this->assertSame('backto/v1', $route->getNamespace());
        $this->assertSame('/security/health', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertNotNull($route->getPermissionCallback());
    }

    public function testScanRouteImplementsRestRouteInterface(): void
    {
        $integrityMonitor = $this->createMock(FileIntegrityMonitor::class);
        $malwareScanner = $this->createMock(MalwareScanner::class);
        $route = new SecurityScanRoute($integrityMonitor, $malwareScanner);

        $this->assertInstanceOf(RestRouteInterface::class, $route);
    }

    public function testScanRouteConfig(): void
    {
        $integrityMonitor = $this->createMock(FileIntegrityMonitor::class);
        $malwareScanner = $this->createMock(MalwareScanner::class);
        $route = new SecurityScanRoute($integrityMonitor, $malwareScanner);

        $this->assertSame('backto/v1', $route->getNamespace());
        $this->assertSame('/security/scan', $route->getRoute());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertNotNull($route->getPermissionCallback());
    }

    public function testAllRoutesRequireManageOptions(): void
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $healthCheck = $this->createMock(SecurityHealthCheck::class);
        $integrityMonitor = $this->createMock(FileIntegrityMonitor::class);
        $malwareScanner = $this->createMock(MalwareScanner::class);

        $routes = [
            new SecurityAuditLogRoute($repository),
            new SecurityHealthRoute($healthCheck),
            new SecurityScanRoute($integrityMonitor, $malwareScanner),
        ];

        foreach ($routes as $route) {
            $callback = $route->getPermissionCallback();
            $this->assertNotNull($callback);
            $this->assertIsCallable($callback);
        }
    }
}
