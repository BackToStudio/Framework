<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleTagManagerScript;
use PHPUnit\Framework\TestCase;

class GoogleTagManagerScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertSame('google-tag-manager', $script->getHandle());
    }

    public function testCategoryKey(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertSame('analytics', $script->getCategoryKey());
    }

    public function testSourceContainsContainerId(): void
    {
        $script = new GoogleTagManagerScript('GTM-ABC123');
        $this->assertStringContainsString('GTM-ABC123', $script->getSource());
        $this->assertStringContainsString('googletagmanager.com/gtm.js', $script->getSource());
    }

    public function testIsInline(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertTrue($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertSame('head', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new GoogleTagManagerScript('GTM-XXXX');
        $this->assertSame(1, $script->getPriority());
    }
}
