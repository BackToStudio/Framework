<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\FileWriterInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Hardening\DirectoryProtection;
use PHPUnit\Framework\TestCase;

/**
 * In-memory FileWriter for testing.
 */
class InMemoryFileWriter implements FileWriterInterface
{
    /** @var array<string, string> */
    private array $files = [];

    public function write(string $path, string $content): bool
    {
        $this->files[$path] = $content;

        return true;
    }

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * @return array<string, string>
     */
    public function getWrittenFiles(): array
    {
        return $this->files;
    }
}

/**
 * Testable subclass to control upload dir.
 */
class TestableDirectoryProtection extends DirectoryProtection
{
    private ?string $uploadDir = null;

    public function setUploadDir(?string $dir): void
    {
        $this->uploadDir = $dir;
    }

    protected function getUploadDir(): ?string
    {
        return $this->uploadDir;
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
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $fileWriter = new InMemoryFileWriter();
        $rule = new TestableDirectoryProtection($dispatcher, $fileWriter);
        $rule->setUploadDir('/var/www/uploads');

        $rule->ensureProtection();

        $written = $fileWriter->getWrittenFiles();
        $this->assertArrayHasKey('/var/www/uploads/.htaccess', $written);
        $this->assertArrayHasKey('/var/www/uploads/index.php', $written);
        $this->assertStringContainsString('Options -Indexes', $written['/var/www/uploads/.htaccess']);
        $this->assertStringContainsString('Silence is golden', $written['/var/www/uploads/index.php']);
    }

    public function testEnsureProtectionSkipsNullUploadDir(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $fileWriter = new InMemoryFileWriter();
        $rule = new TestableDirectoryProtection($dispatcher, $fileWriter);
        $rule->setUploadDir(null);

        $rule->ensureProtection();

        $this->assertEmpty($fileWriter->getWrittenFiles());
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
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $fileWriter = new InMemoryFileWriter();
        $rule = new TestableDirectoryProtection($dispatcher, $fileWriter);
        $rule->setUploadDir('/var/www/uploads');

        $rule->activate();

        $this->assertNotEmpty($fileWriter->getWrittenFiles());
    }
}
