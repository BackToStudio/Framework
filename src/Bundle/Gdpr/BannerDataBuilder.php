<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;

/**
 * Builds the data/configuration for the GDPR consent banner.
 *
 * Responsible for transforming consent categories and storage state
 * into serializable data structures used by the banner renderer.
 */
final class BannerDataBuilder
{
    public function __construct(
        private readonly ConsentCategoryRegistry $categoryRegistry,
        private readonly ConsentStorageInterface $consentStorage,
    ) {
    }

    /**
     * @param ConsentCategoryInterface[] $categories
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    public function buildCategoriesData(array $categories): array
    {
        $data = [];

        foreach ($categories as $category) {
            $data[] = [
                'key' => $category->getKey(),
                'label' => $category->getLabel(),
                'required' => $category->isRequired(),
            ];
        }

        return $data;
    }

    public function getCategoriesJson(): string
    {
        $categories = $this->categoryRegistry->getCategories();

        return json_encode(
            $this->buildCategoriesData($categories),
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }

    public function isConsentGiven(): bool
    {
        return $this->consentStorage->isConsentGiven();
    }

    public function getConsentGivenJs(): string
    {
        return $this->consentStorage->isConsentGiven() ? 'true' : 'false';
    }

    public function getCurrentConsentJson(): string
    {
        return json_encode($this->consentStorage->getConsent(), JSON_THROW_ON_ERROR | JSON_HEX_TAG);
    }
}
