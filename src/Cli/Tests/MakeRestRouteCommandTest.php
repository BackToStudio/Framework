<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Tests;

use BackTo\Framework\Cli\Command\MakeRestRouteCommand;
use BackTo\Framework\Cli\Infrastructure\NativeFilesystem;
use BackTo\Framework\Cli\Infrastructure\WpCliOutput;
use PHPUnit\Framework\TestCase;

class MakeRestRouteCommandTest extends TestCase
{
    private MakeRestRouteCommand $command;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->command = new MakeRestRouteCommand(new NativeFilesystem(), new WpCliOutput());
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

    public function testGeneratesRestRouteFile(): void
    {
        ($this->command)(['GetEvents'], ['dir' => $this->outputDir]);

        $file = $this->outputDir . '/GetEvents.php';
        $this->assertFileExists($file);

        $content = (string) \file_get_contents($file);
        $this->assertStringContainsString('class GetEvents implements RestRouteInterface', $content);
        $this->assertStringContainsString("return 'app/v1';", $content);
        $this->assertStringContainsString("return '/get-events';", $content);
    }

    public function testCustomRouteNamespace(): void
    {
        ($this->command)(['GetEvents'], ['dir' => $this->outputDir, 'route-namespace' => 'myapp/v2']);

        $content = (string) \file_get_contents($this->outputDir . '/GetEvents.php');
        $this->assertStringContainsString("return 'myapp/v2';", $content);
    }
}
