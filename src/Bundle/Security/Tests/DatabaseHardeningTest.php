<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Bundle\Security\DatabaseHardening;
use PHPUnit\Framework\TestCase;

class TestableDatabaseHardening extends DatabaseHardening
{
    private bool $safeCaller = false;

    public function setSafeCaller(bool $safe): void
    {
        $this->safeCaller = $safe;
    }

    protected function isFromSafeCaller(): bool
    {
        return $this->safeCaller;
    }
}

class DatabaseHardeningTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private RequestContextInterface $requestContext;
    private TestableDatabaseHardening $hardening;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->hardening = new TestableDatabaseHardening($this->dispatcher, $this->logger, $this->requestContext, true);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->hardening);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->hardening);
    }

    public function testGetName(): void
    {
        $this->assertSame('database_hardening', $this->hardening->getName());
    }

    public function testHooksRegistersQueryFilter(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('query', $this->anything());

        $this->hardening->hooks();
    }

    public function testDetectDangerousPatternsDropTable(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns('DROP TABLE wp_users');

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsTruncate(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns('TRUNCATE TABLE wp_posts');

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsUnionSelect(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT * FROM wp_users WHERE id = 1 UNION SELECT * FROM wp_options");

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsSleep(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT * FROM wp_users WHERE id = SLEEP(5)");

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsLoadFile(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT LOAD_FILE('/etc/passwd')");

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsIntoOutfile(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT * INTO OUTFILE '/tmp/data.csv' FROM wp_users");

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsBenchmark(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT BENCHMARK(1000000, SHA1('test'))");

        $this->assertNotEmpty($patterns);
    }

    public function testDetectDangerousPatternsAlterTable(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("ALTER TABLE wp_users ADD COLUMN hack VARCHAR(255)");

        $this->assertNotEmpty($patterns);
    }

    public function testSafeQueryReturnsEmpty(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns("SELECT * FROM wp_posts WHERE ID = 1");

        $this->assertSame([], $patterns);
    }

    public function testInspectQueryLogsWarningForDangerousQuery(): void
    {
        $this->logger->expects($this->once())->method('warning');

        $this->hardening->inspectQuery('DROP TABLE wp_users');
    }

    public function testInspectQuerySkipsWarningForSafeCaller(): void
    {
        $this->hardening->setSafeCaller(true);

        $this->logger->expects($this->never())->method('warning');

        $this->hardening->inspectQuery('DROP TABLE wp_users');
    }

    public function testInspectQueryReturnsOriginalQuery(): void
    {
        $query = "SELECT * FROM wp_posts WHERE ID = 1";

        $this->assertSame($query, $this->hardening->inspectQuery($query));
    }

    public function testDetectUnpreparedQueryLogsInfo(): void
    {
        $this->logger->expects($this->once())->method('info');

        $this->hardening->detectUnpreparedQuery("SELECT * FROM wp_users WHERE username = 'admin'");
    }

    public function testDetectUnpreparedQuerySkipsPreparedQueries(): void
    {
        $this->logger->expects($this->never())->method('info');

        $this->hardening->detectUnpreparedQuery("SELECT * FROM wp_users WHERE username = %s");
    }

    public function testDetectUnpreparedQuerySkipsSafeCaller(): void
    {
        $this->hardening->setSafeCaller(true);
        $this->logger->expects($this->never())->method('info');

        $this->hardening->detectUnpreparedQuery("SELECT * FROM wp_users WHERE username = 'admin'");
    }

    public function testDebugMode(): void
    {
        $this->assertTrue($this->hardening->isDebugMode());

        $nonDebug = new TestableDatabaseHardening($this->dispatcher, $this->logger, $this->requestContext, false);
        $this->assertFalse($nonDebug->isDebugMode());
    }

    public function testInspectQuerySkipsUnpreparedDetectionWhenNotDebug(): void
    {
        $nonDebug = new TestableDatabaseHardening($this->dispatcher, $this->logger, $this->requestContext, false);

        // No info log for unprepared queries when not in debug mode
        $this->logger->expects($this->never())->method('info');

        $nonDebug->inspectQuery("SELECT * FROM wp_users WHERE username = 'admin'");
    }

    public function testCaseInsensitivePatternDetection(): void
    {
        $patterns = $this->hardening->detectDangerousPatterns('drop table wp_users');
        $this->assertNotEmpty($patterns);

        $patterns = $this->hardening->detectDangerousPatterns('Drop Table wp_users');
        $this->assertNotEmpty($patterns);
    }
}
