<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\DirectoryProtection;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control upload dir and file writing.
 */
class TestableDirectoryProtection extends DirectoryProtection
{
    private ?string $uploadDir = null;

    /** @var array<string, string> */
    private array $writtenFiles = [];

    public function setUploadDir(?string $dir): void
    {
        $this->uploadDir = $dir;
    }

    /**
     * @return array<string, string>
     */
    public function getWrittenFiles(): array
    {
        return $this->writtenFiles;
    }

    protected function getUploadDir(): ?string
    {
        return $this->uploadDir;
    }

    protected function writeProtectionFile(string $path, string $content): bool
    {
        if (file_exists($path)) {
            return true;
        }

        $dir = dirname($path);

        if (!is_dir($dir)) {
            return false;
        }

        $this->writtenFiles[$path] = $content;

        return true;
    }
}

class DirectoryProtectionTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DirectoryProtection($dispatcher);

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(ActivationHooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DirectoryProtection($dispatcher);

        $this->assertSame('directory_protection', $rule->getName());
    }

    public function testHooksRegistersAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('admin_init', $this->anything());

        $rule = new DirectoryProtection($dispatcher);
        $rule->hooks();
    }

    public function testEnsureProtectionWritesFiles(): void
    {
        $tmpDir = sys_get_temp_dir() . '/backto_test_uploads_' . bin2hex(random_bytes(4));
        mkdir($tmpDir, 0755, true);

        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDirectoryProtection($dispatcher);
        $rule->setUploadDir($tmpDir);

        $rule->ensureProtection();

        $written = $rule->getWrittenFiles();
        $this->assertArrayHasKey($tmpDir . '/.htaccess', $written);
        $this->assertArrayHasKey($tmpDir . '/index.php', $written);
        $this->assertStringContainsString('Options -Indexes', $written[$tmpDir . '/.htaccess']);
        $this->assertStringContainsString('Silence is golden', $written[$tmpDir . '/index.php']);

        rmdir($tmpDir);
    }

    public function testEnsureProtectionSkipsNullUploadDir(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDirectoryProtection($dispatcher);
        $rule->setUploadDir(null);

        $rule->ensureProtection();

        $this->assertEmpty($rule->getWrittenFiles());
    }

    public function testGetProtectedPaths(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDirectoryProtection($dispatcher);
        $rule->setUploadDir('/var/www/wp-content/uploads');

        $paths = $rule->getProtectedPaths();

        $this->assertCount(2, $paths);
        $this->assertContains('/var/www/wp-content/uploads/.htaccess', $paths);
        $this->assertContains('/var/www/wp-content/uploads/index.php', $paths);
    }

    public function testGetProtectedPathsEmptyWhenNoUploadDir(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDirectoryProtection($dispatcher);
        $rule->setUploadDir(null);

        $this->assertEmpty($rule->getProtectedPaths());
    }

    public function testActivateCallsEnsureProtection(): void
    {
        $tmpDir = sys_get_temp_dir() . '/backto_test_uploads_' . bin2hex(random_bytes(4));
        mkdir($tmpDir, 0755, true);

        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDirectoryProtection($dispatcher);
        $rule->setUploadDir($tmpDir);

        $rule->activate();

        $this->assertNotEmpty($rule->getWrittenFiles());

        rmdir($tmpDir);
    }
}
