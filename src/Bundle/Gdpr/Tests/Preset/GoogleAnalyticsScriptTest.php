<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAnalyticsScript;
use PHPUnit\Framework\TestCase;

class GoogleAnalyticsScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertSame('google-analytics', $script->getHandle());
    }

    public function testCategoryKey(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertSame('analytics', $script->getCategoryKey());
    }

    public function testSourceContainsMeasurementId(): void
    {
        $script = new GoogleAnalyticsScript('G-ABC123');
        $source = $script->getSource();
        $this->assertStringContainsString('G-ABC123', $source);
        $this->assertStringContainsString('gtag', $source);
    }

    public function testIsInline(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertTrue($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertSame('head', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new GoogleAnalyticsScript('G-XXXX');
        $this->assertSame(5, $script->getPriority());
    }
}
