<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests\Entity;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\Entity\TrackingScript;
use PHPUnit\Framework\TestCase;

class TrackingScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new TrackingScript('ga', 'analytics', 'https://example.com/ga.js');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testGetters(): void
    {
        $script = new TrackingScript('ga', 'analytics', 'https://example.com/ga.js', false, 'footer', 20);

        $this->assertSame('ga', $script->getHandle());
        $this->assertSame('analytics', $script->getCategoryKey());
        $this->assertSame('https://example.com/ga.js', $script->getSource());
        $this->assertFalse($script->isInline());
        $this->assertSame('footer', $script->getLocation());
        $this->assertSame(20, $script->getPriority());
    }

    public function testInlineScript(): void
    {
        $script = new TrackingScript('gtag-config', 'analytics', "gtag('config', 'G-XXX');", true);

        $this->assertTrue($script->isInline());
        $this->assertSame("gtag('config', 'G-XXX');", $script->getSource());
    }

    public function testDefaultValues(): void
    {
        $script = new TrackingScript('pixel', 'marketing', 'https://example.com/pixel.js');

        $this->assertFalse($script->isInline());
        $this->assertSame('head', $script->getLocation());
        $this->assertSame(10, $script->getPriority());
    }
}
