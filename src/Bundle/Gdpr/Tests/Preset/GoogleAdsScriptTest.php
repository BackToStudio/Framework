<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAdsScript;
use PHPUnit\Framework\TestCase;

class GoogleAdsScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertSame('google-ads', $script->getHandle());
    }

    public function testCategoryKey(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertSame('marketing', $script->getCategoryKey());
    }

    public function testSourceContainsConversionId(): void
    {
        $script = new GoogleAdsScript('AW-123456');
        $source = $script->getSource();
        $this->assertStringContainsString('AW-123456', $source);
        $this->assertStringContainsString('gtag', $source);
    }

    public function testIsInline(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertTrue($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertSame('head', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new GoogleAdsScript('AW-XXXX');
        $this->assertSame(5, $script->getPriority());
    }
}
