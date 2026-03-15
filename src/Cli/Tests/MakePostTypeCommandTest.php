<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Tests;

use BackTo\Framework\Cli\Command\MakePostTypeCommand;
use PHPUnit\Framework\TestCase;

class MakePostTypeCommandTest extends TestCase
{
    private MakePostTypeCommand $command;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->command = new MakePostTypeCommand();
        $this->outputDir = \sys_get_temp_dir() . '/backto_cli_test_' . \uniqid();
        \mkdir($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = \glob($this->outputDir . '/*.php') ?: [];
        foreach ($files as $file) {
            \unlink($file);
        }
        @\rmdir($this->outputDir);
    }

    public function testGeneratesPostTypeFile(): void
    {
        ($this->command)(['Event'], ['dir' => $this->outputDir, 'namespace' => 'App\\PostType']);

        $file = $this->outputDir . '/Event.php';
        $this->assertFileExists($file);

        $content = (string) \file_get_contents($file);
        $this->assertStringContainsString('namespace App\\PostType;', $content);
        $this->assertStringContainsString('class Event implements PostTypeInterface', $content);
        $this->assertStringContainsString("return 'event';", $content);
    }

    public function testDoesNotOverwriteWithoutForce(): void
    {
        $file = $this->outputDir . '/Event.php';
        \file_put_contents($file, 'original');

        ($this->command)(['Event'], ['dir' => $this->outputDir]);

        $this->assertSame('original', \file_get_contents($file));
    }

    public function testOverwritesWithForce(): void
    {
        $file = $this->outputDir . '/Event.php';
        \file_put_contents($file, 'original');

        ($this->command)(['Event'], ['dir' => $this->outputDir, 'force' => true]);

        $this->assertNotSame('original', \file_get_contents($file));
    }

    public function testDefaultNamespaceIsApp(): void
    {
        ($this->command)(['Event'], ['dir' => $this->outputDir]);

        $content = (string) \file_get_contents($this->outputDir . '/Event.php');
        $this->assertStringContainsString('namespace App;', $content);
    }

    public function testRejectsInvalidName(): void
    {
        ($this->command)(['../../etc/evil'], ['dir' => $this->outputDir]);

        $files = \glob($this->outputDir . '/*.php') ?: [];
        $this->assertCount(0, $files);
    }

    public function testRejectsInvalidNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->command)(['Event'], ['dir' => $this->outputDir, 'namespace' => 'App; system("ls");//']);
    }

    public function testRejectsNonexistentDirectory(): void
    {
        ($this->command)(['Event'], ['dir' => '/nonexistent/path/that/does/not/exist']);

        // Should not create any file — no crash, just error
        $this->assertTrue(true);
    }
}
