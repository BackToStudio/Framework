<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Gdpr\ConsentBanner;
use BackTo\Framework\Bundle\Gdpr\ConsentCategoryRegistry;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;
use BackTo\Framework\Bundle\Gdpr\Entity\TrackingScript;
use BackTo\Framework\Bundle\Gdpr\RegisterGdpr;
use BackTo\Framework\Bundle\Gdpr\TrackingScriptRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegisterGdprTest extends TestCase
{
    private ConsentCategoryRegistry $categoryRegistry;
    private TrackingScriptRegistry $scriptRegistry;
    private ConsentStorageInterface&MockObject $consentStorage;
    private HookDispatcherInterface&MockObject $hookDispatcher;
    private ConsentBanner&MockObject $consentBanner;
    private RegisterGdpr $registerGdpr;

    protected function setUp(): void
    {
        $this->categoryRegistry = new ConsentCategoryRegistry();
        $this->scriptRegistry = new TrackingScriptRegistry();
        $this->consentStorage = $this->createMock(ConsentStorageInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->consentBanner = $this->createMock(ConsentBanner::class);

        $this->registerGdpr = new RegisterGdpr(
            $this->categoryRegistry,
            $this->scriptRegistry,
            $this->consentStorage,
            $this->hookDispatcher,
            $this->consentBanner,
        );
    }

    public function testHooksRegistersThreeActions(): void
    {
        $this->hookDispatcher->expects($this->exactly(3))
            ->method('addAction')
            ->willReturnCallback(function (string $hook): void {
                $this->assertContains($hook, ['wp_head', 'wp_footer']);
            });

        $this->registerGdpr->hooks();
    }

    public function testRenderHeadScriptsOutputsAllowedScripts(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));
        $this->scriptRegistry->add(new TrackingScript('ga', 'analytics', "console.log('ga');", true, 'head'));

        $this->consentStorage->method('hasConsent')
            ->with('analytics')
            ->willReturn(true);

        ob_start();
        $this->registerGdpr->renderHeadScripts();
        $output = ob_get_clean();

        $this->assertStringContainsString("console.log('ga');", $output);
    }

    public function testRenderHeadScriptsBlocksNonConsentedScripts(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('marketing', 'Marketing'));
        $this->scriptRegistry->add(new TrackingScript('pixel', 'marketing', "console.log('pixel');", true, 'head'));

        $this->consentStorage->method('hasConsent')
            ->with('marketing')
            ->willReturn(false);

        ob_start();
        $this->registerGdpr->renderHeadScripts();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRequiredCategoryScriptsAlwaysLoad(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('necessary', 'Necessaire', '', true));
        $this->scriptRegistry->add(new TrackingScript('essential', 'necessary', "console.log('ok');", true, 'head'));

        $this->consentStorage->method('hasConsent')->willReturn(false);

        ob_start();
        $this->registerGdpr->renderHeadScripts();
        $output = ob_get_clean();

        $this->assertStringContainsString("console.log('ok');", $output);
    }

    public function testRenderFooterScriptsOnlyOutputsFooterLocation(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));
        $this->scriptRegistry->add(new TrackingScript('head-script', 'analytics', "head();", true, 'head'));
        $this->scriptRegistry->add(new TrackingScript('footer-script', 'analytics', "footer();", true, 'footer'));

        $this->consentStorage->method('hasConsent')->willReturn(true);

        ob_start();
        $this->registerGdpr->renderFooterScripts();
        $output = ob_get_clean();

        $this->assertStringContainsString('footer();', $output);
        $this->assertStringNotContainsString('head();', $output);
    }

    public function testExternalScriptRendersWithSrcAttribute(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));
        $this->scriptRegistry->add(new TrackingScript('ga', 'analytics', 'https://example.com/ga.js', false, 'head'));

        $this->consentStorage->method('hasConsent')->willReturn(true);

        ob_start();
        $this->registerGdpr->renderHeadScripts();
        $output = ob_get_clean();

        $this->assertStringContainsString('src="https://example.com/ga.js"', $output);
    }

    public function testRenderConsentBannerDelegatesToBanner(): void
    {
        $this->consentBanner->expects($this->once())
            ->method('render')
            ->willReturn('<div>banner</div>');

        ob_start();
        $this->registerGdpr->renderConsentBanner();
        $output = ob_get_clean();

        $this->assertSame('<div>banner</div>', $output);
    }
}
