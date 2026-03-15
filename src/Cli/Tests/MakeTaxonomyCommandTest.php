<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Tests;

use BackTo\Framework\Cli\Command\MakeTaxonomyCommand;
use PHPUnit\Framework\TestCase;

class MakeTaxonomyCommandTest extends TestCase
{
    private MakeTaxonomyCommand $command;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->command = new MakeTaxonomyCommand();
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

    public function testGeneratesTaxonomyFile(): void
    {
        ($this->command)(['EventCategory'], ['dir' => $this->outputDir]);

        $file = $this->outputDir . '/EventCategory.php';
        $this->assertFileExists($file);

        $content = (string) \file_get_contents($file);
        $this->assertStringContainsString('class EventCategory implements TaxonomyInterface', $content);
        $this->assertStringContainsString("return 'event_category';", $content);
    }

    public function testIncludesPostTypes(): void
    {
        ($this->command)(['EventCategory'], ['dir' => $this->outputDir, 'post-types' => 'event,post']);

        $content = (string) \file_get_contents($this->outputDir . '/EventCategory.php');
        $this->assertStringContainsString("'event', 'post'", $content);
    }

    public function testEmptyPostTypesByDefault(): void
    {
        ($this->command)(['EventCategory'], ['dir' => $this->outputDir]);

        $content = (string) \file_get_contents($this->outputDir . '/EventCategory.php');
        $this->assertStringContainsString('return [];', $content);
    }
}
