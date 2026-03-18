<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Security\AuditLogAdminPage;
use BackTo\Framework\Security\AuditLogCsvExporter;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use PHPUnit\Framework\TestCase;

class TestableAuditLogAdminPage extends AuditLogAdminPage
{
    /** @var array<string, mixed> */
    private array $requestFilters = [];
    private int $currentPage = 1;
    private bool $exportRequest = false;
    private bool $purgeRequest = false;
    private int $purgeDays = 90;

    /**
     * @param array<string, mixed> $filters
     */
    public function setRequestFilters(array $filters): void
    {
        $this->requestFilters = $filters;
    }

    public function setCurrentPageNumber(int $page): void
    {
        $this->currentPage = $page;
    }

    public function setExportRequest(bool $export): void
    {
        $this->exportRequest = $export;
    }

    public function setPurgeRequest(bool $purge): void
    {
        $this->purgeRequest = $purge;
    }

    public function setPurgeDays(int $days): void
    {
        $this->purgeDays = $days;
    }

    protected function getFiltersFromRequest(): array
    {
        return $this->requestFilters;
    }

    protected function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    protected function isExportRequest(): bool
    {
        return $this->exportRequest;
    }

    protected function isPurgeRequest(): bool
    {
        return $this->purgeRequest;
    }

    protected function getPurgeDays(): int
    {
        return $this->purgeDays;
    }

    protected function renderNotice(string $message): void
    {
        echo '<div class="notice">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    protected function verifyNonce(string $action, string $queryArg): bool
    {
        return true;
    }

    protected function renderNonceField(string $action, string $name): void
    {
        // No-op in tests
    }
}

class AuditLogAdminPageTest extends TestCase
{
    private AuditLogRepositoryInterface $repository;
    private RequestContextInterface $requestContext;
    private TestableAuditLogAdminPage $page;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->page = new TestableAuditLogAdminPage($this->repository, $this->requestContext);
    }

    public function testImplementsAdminPageInterface(): void
    {
        $this->assertInstanceOf(AdminPageInterface::class, $this->page);
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame('Security Audit Log', $this->page->getPageTitle());
    }

    public function testGetMenuTitle(): void
    {
        $this->assertSame('Audit Log', $this->page->getMenuTitle());
    }

    public function testGetCapability(): void
    {
        $this->assertSame('manage_options', $this->page->getCapability());
    }

    public function testGetMenuSlug(): void
    {
        $this->assertSame('backto-audit-log', $this->page->getMenuSlug());
    }

    public function testGetIconUrl(): void
    {
        $this->assertSame('dashicons-shield', $this->page->getIconUrl());
    }

    public function testGetPosition(): void
    {
        $this->assertSame(81, $this->page->getPosition());
    }

    public function testRenderPageOutputsTable(): void
    {
        $events = [
            [
                'timestamp' => 1700000000,
                'event' => 'login_success',
                'severity' => 'info',
                'context' => ['username' => 'admin', 'ip' => '1.2.3.4'],
            ],
        ];

        ob_start();
        $this->page->renderPage($events, [], 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('Security Audit Log', $output);
        $this->assertStringContainsString('login_success', $output);
        $this->assertStringContainsString('INFO', $output);
        $this->assertStringContainsString('admin', $output);
    }

    public function testRenderPageShowsEmptyMessage(): void
    {
        ob_start();
        $this->page->renderPage([], [], 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('No events found', $output);
    }

    public function testRenderFilterForm(): void
    {
        ob_start();
        $this->page->renderFilterForm(['event' => 'login_failed']);
        $output = ob_get_clean();

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('login_failed', $output);
        $this->assertStringContainsString('Filter', $output);
    }

    public function testRenderActions(): void
    {
        ob_start();
        $this->page->renderActions();
        $output = ob_get_clean();

        $this->assertStringContainsString('Export CSV', $output);
        $this->assertStringContainsString('Purge', $output);
    }

    public function testRenderCallsRepositoryWithFilters(): void
    {
        $this->page->setRequestFilters(['event' => 'login_failed']);

        $this->repository->expects($this->once())
            ->method('getEvents')
            ->with(['event' => 'login_failed'], 50, 0)
            ->willReturn([]);

        ob_start();
        $this->page->render();
        ob_get_clean();
    }

    public function testRenderWithPagination(): void
    {
        $this->page->setCurrentPageNumber(3);

        $this->repository->expects($this->once())
            ->method('getEvents')
            ->with([], 50, 100) // offset = (3-1) * 50
            ->willReturn([]);

        ob_start();
        $this->page->render();
        ob_get_clean();
    }

    public function testRenderHandlesPurge(): void
    {
        $this->page->setPurgeRequest(true);
        $this->page->setPurgeDays(30);

        $this->repository->expects($this->once())
            ->method('purge')
            ->with(30)
            ->willReturn(15);

        $this->repository->method('getEvents')->willReturn([]);

        ob_start();
        $this->page->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('Purged 15 events', $output);
    }

    public function testRenderSeverityColors(): void
    {
        $events = [
            ['timestamp' => time(), 'event' => 'test', 'severity' => 'critical', 'context' => []],
            ['timestamp' => time(), 'event' => 'test', 'severity' => 'warning', 'context' => []],
            ['timestamp' => time(), 'event' => 'test', 'severity' => 'info', 'context' => []],
        ];

        ob_start();
        $this->page->renderTable($events);
        $output = ob_get_clean();

        $this->assertStringContainsString('#dc3232', $output); // critical
        $this->assertStringContainsString('#dba617', $output); // warning
        $this->assertStringContainsString('#72aee6', $output); // info
    }

    public function testExportCsvOutputsData(): void
    {
        $this->repository->method('getEvents')->willReturn([
            ['timestamp' => 1700000000, 'event' => 'login_success', 'severity' => 'info', 'context' => ['ip' => '1.2.3.4']],
        ]);

        $exporter = new class ($this->repository) extends AuditLogCsvExporter {
            protected function sendCsvHeaders(): void
            {
                // No-op in tests
            }
        };

        ob_start();
        $exporter->export([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Timestamp', $output);
        $this->assertStringContainsString('login_success', $output);
    }
}
