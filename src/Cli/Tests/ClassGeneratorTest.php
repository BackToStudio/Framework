<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Tests;

use BackTo\Framework\Cli\Generator\ClassGenerator;
use PHPUnit\Framework\TestCase;

class ClassGeneratorTest extends TestCase
{
    private ClassGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ClassGenerator();
    }

    public function testGenerateReplacesPlaceholders(): void
    {
        $template = 'class {{className}} in {{namespace}}';
        $replacements = ['{{className}}' => 'Event', '{{namespace}}' => 'App'];

        $result = $this->generator->generate($template, $replacements);

        $this->assertSame('class Event in App', $result);
    }

    public function testToClassNameFromSnakeCase(): void
    {
        $this->assertSame('EventCategory', $this->generator->toClassName('event_category'));
    }

    public function testToClassNameFromKebabCase(): void
    {
        $this->assertSame('EventCategory', $this->generator->toClassName('event-category'));
    }

    public function testToClassNameFromSpaces(): void
    {
        $this->assertSame('EventCategory', $this->generator->toClassName('event category'));
    }

    public function testToClassNameAlreadyPascalCase(): void
    {
        $this->assertSame('Event', $this->generator->toClassName('Event'));
    }

    public function testToKeyFromPascalCase(): void
    {
        $this->assertSame('event_category', $this->generator->toKey('EventCategory'));
    }

    public function testToKeyFromSingleWord(): void
    {
        $this->assertSame('event', $this->generator->toKey('Event'));
    }

    public function testWriteFileCreatesDirectoryAndFile(): void
    {
        $dir = \sys_get_temp_dir() . '/backto_test_' . \uniqid();
        $path = $dir . '/Test.php';

        try {
            $result = $this->generator->writeFile($path, '<?php // test');

            $this->assertTrue($result);
            $this->assertFileExists($path);
            $this->assertSame('<?php // test', \file_get_contents($path));
        } finally {
            @\unlink($path);
            @\rmdir($dir);
        }
    }
}
