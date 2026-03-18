<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Tests;

use BackTo\Framework\Gdpr\ConsentBanner;
use BackTo\Framework\Gdpr\ConsentBannerRenderer;
use BackTo\Framework\Gdpr\ConsentCategoryRegistry;
use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Gdpr\Entity\ConsentCategory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConsentBannerTest extends TestCase
{
    private ConsentCategoryRegistry $categoryRegistry;
    private ConsentStorageInterface&MockObject $consentStorage;
    private ConsentBanner $banner;

    protected function setUp(): void
    {
        $this->categoryRegistry = new ConsentCategoryRegistry();
        $this->consentStorage = $this->createMock(ConsentStorageInterface::class);
        $this->banner = new ConsentBanner(
            $this->categoryRegistry,
            $this->consentStorage,
            new ConsentBannerRenderer(),
        );
    }

    public function testRenderReturnsEmptyStringWithNoCategories(): void
    {
        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $this->assertSame('', $this->banner->render());
    }

    public function testRenderContainsCategoryLabels(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique', 'Mesure'));
        $this->categoryRegistry->add(new ConsentCategory('marketing', 'Marketing', 'Publicite'));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('Analytique', $html);
        $this->assertStringContainsString('Marketing', $html);
        $this->assertStringContainsString('Mesure', $html);
        $this->assertStringContainsString('Publicite', $html);
    }

    public function testRenderContainsThreeButtons(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('Tout accepter', $html);
        $this->assertStringContainsString('Tout refuser', $html);
        $this->assertStringContainsString('Enregistrer mes choix', $html);
    }

    public function testRequiredCategoryHasDisabledCheckbox(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('necessary', 'Necessaire', '', true));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function testNonRequiredCategoryHasNoDisabledAttribute(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('marketing', 'Marketing'));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('id="gdpr-cat-marketing"', $html);
        // The checkbox should not have disabled attribute
        $this->assertDoesNotMatchRegularExpression('/gdpr-cat-marketing[^>]*disabled/', $html);
    }

    public function testRenderContainsCookieJavaScript(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('gdpr_consent', $html);
        $this->assertStringContainsString('setCookie', $html);
        $this->assertStringContainsString('saveConsent', $html);
    }

    public function testRenderContainsToggleButton(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique'));

        $this->consentStorage->method('isConsentGiven')->willReturn(false);
        $this->consentStorage->method('getConsent')->willReturn([]);

        $html = $this->banner->render();

        $this->assertStringContainsString('gdpr-consent-toggle', $html);
        $this->assertStringContainsString('Gerer les cookies', $html);
    }
}
