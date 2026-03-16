<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Gdpr\Entity\TrackingScript;
use BackTo\Framework\Gdpr\TrackingScriptRegistry;
use PHPUnit\Framework\TestCase;

class TrackingScriptRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new TrackingScriptRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new TrackingScriptRegistry();
        $this->assertCount(0, $registry->getScripts());
    }

    public function testAddScripts(): void
    {
        $registry = new TrackingScriptRegistry();

        $registry->add(new TrackingScript('ga', 'analytics', 'https://example.com/ga.js'));
        $registry->add(new TrackingScript('pixel', 'marketing', 'https://example.com/pixel.js'));

        $this->assertCount(2, $registry->getScripts());
    }

    public function testGetScriptsByCategory(): void
    {
        $registry = new TrackingScriptRegistry();

        $registry->add(new TrackingScript('ga', 'analytics', 'https://example.com/ga.js'));
        $registry->add(new TrackingScript('gtag', 'analytics', "gtag('config');", true));
        $registry->add(new TrackingScript('pixel', 'marketing', 'https://example.com/pixel.js'));

        $analyticsScripts = $registry->getScriptsByCategory('analytics');
        $marketingScripts = $registry->getScriptsByCategory('marketing');

        $this->assertCount(2, $analyticsScripts);
        $this->assertCount(1, $marketingScripts);
    }

    public function testGetScriptsByCategoryReturnsEmptyForUnknown(): void
    {
        $registry = new TrackingScriptRegistry();
        $this->assertCount(0, $registry->getScriptsByCategory('unknown'));
    }

    public function testFluentInterface(): void
    {
        $registry = new TrackingScriptRegistry();
        $result = $registry->add(new TrackingScript('ga', 'analytics', 'https://example.com/ga.js'));

        $this->assertSame($registry, $result);
    }
}
