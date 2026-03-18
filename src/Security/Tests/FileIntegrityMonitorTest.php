<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use BackTo\Framework\Security\Contracts\FileIntegrityRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\FileIntegrityMonitor;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control file system access.
 */
class TestableFileIntegrityMonitor extends FileIntegrityMonitor
{
    /** @var array<string, string> Simulated files: path => content */
    private array $files = [];
    private string $basePath = '/fake/wp';

    /**
     * @param array<string, string> $files
     */
    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function setBasePath(string $path): void
    {
        $this->basePath = $path;
    }

    protected function getBasePath(): string
    {
        return $this->basePath;
    }

    protected function fileExists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    protected function hashFile(string $path): ?string
    {
        if (! isset($this->files[$path])) {
            return null;
        }

        return hash('sha256', $this->files[$path]);
    }
}

class FileIntegrityMonitorTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private FileIntegrityRepositoryInterface $repository;
    private LoggerInterface $logger;
    private CronSchedulerInterface $cronScheduler;
    private TestableFileIntegrityMonitor $monitor;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(FileIntegrityRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $this->monitor = new TestableFileIntegrityMonitor(
            $this->dispatcher,
            $this->repository,
            $this->logger,
            $this->cronScheduler,
        );
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->monitor);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->monitor);
    }

    public function testGetName(): void
    {
        $this->assertSame('file_integrity_monitor', $this->monitor->getName());
    }

    public function testHooksRegistersAdminInitAndCronHook(): void
    {
        $hooks = [];
        $this->dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });

        $this->monitor->hooks();

        $this->assertContains('admin_init', $hooks);
        $this->assertContains('backto_file_integrity_check', $hooks);
    }

    public function testHashFiles(): void
    {
        $this->monitor->setFiles([
            '/fake/wp/wp-config.php' => '<?php // config',
            '/fake/wp/index.php' => '<?php // index',
        ]);

        $hashes = $this->monitor->hashFiles('/fake/wp', ['wp-config.php', 'index.php', 'missing.php']);

        $this->assertCount(2, $hashes);
        $this->assertArrayHasKey('wp-config.php', $hashes);
        $this->assertArrayHasKey('index.php', $hashes);
        $this->assertArrayNotHasKey('missing.php', $hashes);
    }

    public function testCheckDetectsModifiedFile(): void
    {
        $originalHash = hash('sha256', '<?php // original');

        $this->repository->method('getBaseline')->willReturn([
            'wp-config.php' => $originalHash,
        ]);

        $this->monitor->setFiles([
            '/fake/wp/wp-config.php' => '<?php // MODIFIED',
        ]);

        $result = $this->monitor->check();

        $this->assertSame(['wp-config.php'], $result['modified']);
        $this->assertSame([], $result['missing']);
        $this->assertSame([], $result['added']);
    }

    public function testCheckDetectsMissingFile(): void
    {
        $this->repository->method('getBaseline')->willReturn([
            'wp-config.php' => hash('sha256', 'content'),
        ]);

        $this->monitor->setFiles([]);

        $result = $this->monitor->check();

        $this->assertSame([], $result['modified']);
        $this->assertSame(['wp-config.php'], $result['missing']);
    }

    public function testCheckDetectsUnchangedFiles(): void
    {
        $content = '<?php // unchanged';
        $hash = hash('sha256', $content);

        $this->repository->method('getBaseline')->willReturn([
            'wp-config.php' => $hash,
        ]);

        $this->monitor->setFiles([
            '/fake/wp/wp-config.php' => $content,
        ]);

        $result = $this->monitor->check();

        $this->assertSame([], $result['modified']);
        $this->assertSame([], $result['missing']);
        $this->assertSame([], $result['added']);
    }

    public function testCheckWithNoBaselineReturnsEmpty(): void
    {
        $this->repository->method('getBaseline')->willReturn(null);

        $result = $this->monitor->check();

        $this->assertSame([], $result['modified']);
        $this->assertSame([], $result['missing']);
        $this->assertSame([], $result['added']);
    }

    public function testScheduleCheckCreatesBaselineWhenNoneExists(): void
    {
        $this->repository->method('hasBaseline')->willReturn(false);
        $this->repository->expects($this->once())->method('storeBaseline');
        $this->logger->expects($this->once())->method('info');

        $this->monitor->scheduleCheck();
    }

    public function testScheduleCheckLogsWarningWhenFilesModified(): void
    {
        $this->repository->method('hasBaseline')->willReturn(true);
        $this->repository->method('getBaseline')->willReturn([
            'wp-config.php' => 'oldhash',
        ]);

        $this->monitor->setFiles([
            '/fake/wp/wp-config.php' => 'new content',
        ]);

        $this->logger->expects($this->once())->method('warning');

        $this->monitor->scheduleCheck();
    }
}
