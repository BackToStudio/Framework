<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\Preset\HubSpotScript;
use PHPUnit\Framework\TestCase;

class HubSpotScriptTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertInstanceOf(TrackingScriptInterface::class, $script);
    }

    public function testHandle(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertSame('hubspot', $script->getHandle());
    }

    public function testCategoryKey(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertSame('marketing', $script->getCategoryKey());
    }

    public function testSourceContainsPortalId(): void
    {
        $script = new HubSpotScript('99887766');
        $source = $script->getSource();
        $this->assertStringContainsString('99887766', $source);
        $this->assertStringContainsString('js.hs-scripts.com', $source);
    }

    public function testIsNotInline(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertFalse($script->isInline());
    }

    public function testLocation(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertSame('footer', $script->getLocation());
    }

    public function testPriority(): void
    {
        $script = new HubSpotScript('12345678');
        $this->assertSame(10, $script->getPriority());
    }
}
