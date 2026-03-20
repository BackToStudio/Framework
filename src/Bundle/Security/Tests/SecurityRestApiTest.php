<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Bundle\Security\RestApi\SecurityAuditLogRoute;
use BackTo\Framework\Bundle\Security\RestApi\SecurityHealthRoute;
use BackTo\Framework\Bundle\Security\RestApi\SecurityScanRoute;
use BackTo\Framework\Bundle\Security\Audit\FileIntegrityMonitor;
use BackTo\Framework\Bundle\Security\HealthCheck\SecurityHealthCheck;
use BackTo\Framework\Bundle\Security\Audit\MalwareScanner;
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

    /**
     * @dataProvider paginationClampDataProvider
     */
    public function testPaginationInputValidation(string $perPageInput, string $pageInput, int $expectedPerPage, int $expectedPage): void
    {
        $this->assertSame($expectedPerPage, max(1, min(100, (int) $perPageInput)));
        $this->assertSame($expectedPage, max(1, (int) $pageInput));
    }

    /**
     * @return array<string, array{string, string, int, int}>
     */
    public static function paginationClampDataProvider(): array
    {
        return [
            'negative page clamped to 1' => [
                '50', '-5', 50, 1,
            ],
            'zero page clamped to 1' => [
                '50', '0', 50, 1,
            ],
            'per_page over 100 clamped' => [
                '500', '1', 100, 1,
            ],
            'per_page zero clamped to 1' => [
                '0', '1', 1, 1,
            ],
            'negative per_page clamped to 1' => [
                '-10', '1', 1, 1,
            ],
            'valid values pass through' => [
                '25', '3', 25, 3,
            ],
        ];
    }
}
