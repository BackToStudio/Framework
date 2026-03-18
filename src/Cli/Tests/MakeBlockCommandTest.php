<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Tests;

use BackTo\Framework\Cli\Command\MakeBlockCommand;
use BackTo\Framework\Cli\Infrastructure\NativeFilesystem;
use BackTo\Framework\Cli\Infrastructure\WpCliOutput;
use PHPUnit\Framework\TestCase;

class MakeBlockCommandTest extends TestCase
{
    private MakeBlockCommand $command;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->command = new MakeBlockCommand(new NativeFilesystem(), new WpCliOutput());
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

    public function testGeneratesBlockFile(): void
    {
        ($this->command)(['Hero'], ['dir' => $this->outputDir]);

        $file = $this->outputDir . '/Hero.php';
        $this->assertFileExists($file);

        $content = (string) \file_get_contents($file);
        $this->assertStringContainsString('class Hero implements BlockInterface', $content);
        $this->assertStringContainsString("return 'custom/hero';", $content);
    }

    public function testCustomBlockNamespace(): void
    {
        ($this->command)(['Hero'], ['dir' => $this->outputDir, 'block-namespace' => 'mytheme']);

        $content = (string) \file_get_contents($this->outputDir . '/Hero.php');
        $this->assertStringContainsString("return 'mytheme/hero';", $content);
    }
}
