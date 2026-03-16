<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\Preset\GtagLoaderScript;
use PHPUnit\Framework\TestCase;

class GtagLoaderScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertSame('gtag-loader', $script->getHandle());
    }

    public function testDefaultCategoryKey(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertSame('analytics', $script->getCategoryKey());
    }

    public function testCustomCategoryKey(): void
    {
        $script = new GtagLoaderScript('AW-XXXX', 'marketing');
        $this->assertSame('marketing', $script->getCategoryKey());
    }

    public function testSourceContainsTrackingId(): void
    {
        $script = new GtagLoaderScript('G-ABC123');
        $source = $script->getSource();
        $this->assertSame('https://www.googletagmanager.com/gtag/js?id=G-ABC123', $source);
    }

    public function testIsNotInline(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertFalse($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertSame('head', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new GtagLoaderScript('G-XXXX');
        $this->assertSame(4, $script->getPriority());
    }
}
