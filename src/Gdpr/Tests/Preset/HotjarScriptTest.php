<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\Preset\HotjarScript;
use PHPUnit\Framework\TestCase;

class HotjarScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new HotjarScript('123456');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new HotjarScript('123456');
        $this->assertSame('hotjar', $script->getHandle());
    }

    public function testCategoryKey(): void
    {
        $script = new HotjarScript('123456');
        $this->assertSame('analytics', $script->getCategoryKey());
    }

    public function testSourceContainsSiteId(): void
    {
        $script = new HotjarScript('987654');
        $source = $script->getSource();
        $this->assertStringContainsString('987654', $source);
        $this->assertStringContainsString('static.hotjar.com', $source);
    }

    public function testIsInline(): void
    {
        $script = new HotjarScript('123456');
        $this->assertTrue($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new HotjarScript('123456');
        $this->assertSame('head', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new HotjarScript('123456');
        $this->assertSame(10, $script->getPriority());
    }
}
