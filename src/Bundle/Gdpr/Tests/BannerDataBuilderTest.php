<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests;

use BackTo\Framework\Bundle\Gdpr\BannerDataBuilder;
use BackTo\Framework\Bundle\Gdpr\ConsentCategoryRegistry;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BannerDataBuilderTest extends TestCase
{
    private ConsentCategoryRegistry $categoryRegistry;
    private ConsentStorageInterface&MockObject $consentStorage;
    private BannerDataBuilder $builder;

    protected function setUp(): void
    {
        $this->categoryRegistry = new ConsentCategoryRegistry();
        $this->consentStorage = $this->createMock(ConsentStorageInterface::class);
        $this->builder = new BannerDataBuilder($this->categoryRegistry, $this->consentStorage);
    }

    public function testBuildCategoriesDataReturnsStructuredArray(): void
    {
        $category = new ConsentCategory('analytics', 'Analytique', 'Mesure', false);

        $data = $this->builder->buildCategoriesData([$category]);

        $this->assertSame([
            ['key' => 'analytics', 'label' => 'Analytique', 'required' => false],
        ], $data);
    }

    public function testBuildCategoriesDataHandlesRequiredCategory(): void
    {
        $category = new ConsentCategory('necessary', 'Necessaire', '', true);

        $data = $this->builder->buildCategoriesData([$category]);

        $this->assertTrue($data[0]['required']);
    }

    public function testBuildCategoriesDataReturnsEmptyForNoCategories(): void
    {
        $this->assertSame([], $this->builder->buildCategoriesData([]));
    }

    public function testGetCategoriesJsonReturnsValidJson(): void
    {
        $this->categoryRegistry->add(new ConsentCategory('analytics', 'Analytique', 'Mesure'));

        $json = $this->builder->getCategoriesJson();

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $decoded);
        $this->assertSame('analytics', $decoded[0]['key']);
    }

    public function testIsConsentGivenDelegatesToStorage(): void
    {
        $this->consentStorage->method('isConsentGiven')->willReturn(true);

        $this->assertTrue($this->builder->isConsentGiven());
    }

    public function testGetConsentGivenJsReturnsTrueString(): void
    {
        $this->consentStorage->method('isConsentGiven')->willReturn(true);

        $this->assertSame('true', $this->builder->getConsentGivenJs());
    }

    public function testGetConsentGivenJsReturnsFalseString(): void
    {
        $this->consentStorage->method('isConsentGiven')->willReturn(false);

        $this->assertSame('false', $this->builder->getConsentGivenJs());
    }

    public function testGetCurrentConsentJsonReturnsValidJson(): void
    {
        $this->consentStorage->method('getConsent')->willReturn(['analytics' => true]);

        $json = $this->builder->getCurrentConsentJson();
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['analytics' => true], $decoded);
    }
}
