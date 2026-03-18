<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Performance\PreloadUrlCollector;
use PHPUnit\Framework\TestCase;

class PreloadUrlCollectorTest extends TestCase
{
    public function testConstructorDefaultBatchSize(): void
    {
        $collector = new PreloadUrlCollector();
        $this->assertInstanceOf(PreloadUrlCollector::class, $collector);
    }

    public function testConstructorCustomBatchSize(): void
    {
        $collector = new PreloadUrlCollector(100);
        $this->assertInstanceOf(PreloadUrlCollector::class, $collector);
    }
}
