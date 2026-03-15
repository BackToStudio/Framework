<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Tests;

use BackTo\Framework\Assets\Contracts\FileLocatorInterface;
use BackTo\Framework\Assets\SvgFactory;
use PHPUnit\Framework\TestCase;

class SvgFactoryTest extends TestCase
{
    public function testGetFromPathReturnsDecodedContent(): void
    {
        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $factory = new SvgFactory($fileLocator);

        $tmpFile = \tempnam(\sys_get_temp_dir(), 'svg');
        \file_put_contents($tmpFile, '<svg>&lt;test&gt;</svg>');

        $result = $factory->getFromPath($tmpFile);

        $this->assertStringContainsString('<svg>', $result);
        \unlink($tmpFile);
    }

    public function testGetFromPathReturnsEmptyStringForMissingFile(): void
    {
        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $factory = new SvgFactory($fileLocator);

        $result = @$factory->getFromPath('/nonexistent/path.svg');

        $this->assertSame('', $result);
    }

    public function testGetFromIdUsesFileLocator(): void
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'svg');
        \file_put_contents($tmpFile, '<svg>test</svg>');

        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $fileLocator->expects($this->once())
            ->method('getAttachedFile')
            ->with(42)
            ->willReturn($tmpFile);

        $factory = new SvgFactory($fileLocator);
        $result = $factory->getFromId(42);

        $this->assertStringContainsString('<svg>', $result);
        \unlink($tmpFile);
    }

    public function testGetFromSrcWithUploadUrl(): void
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'svg');
        \file_put_contents($tmpFile, '<svg>upload</svg>');

        $basedir = \dirname($tmpFile);
        $filename = \basename($tmpFile);

        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $fileLocator->method('getUploadDir')
            ->willReturn([
                'basedir' => $basedir,
                'baseurl' => 'https://example.com/uploads',
            ]);

        $factory = new SvgFactory($fileLocator);
        $result = $factory->getFromSrc('https://example.com/uploads/' . $filename);

        $this->assertStringContainsString('<svg>', $result);
        \unlink($tmpFile);
    }
}
