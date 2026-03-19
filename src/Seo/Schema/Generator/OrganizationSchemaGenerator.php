<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;
use BackTo\Framework\Seo\SeoManager;

/**
 * Generates an Organization schema from WordPress settings and SEO provider social links.
 *
 * Produces an Organization node with @id "#organization".
 */
class OrganizationSchemaGenerator
{
    private readonly SeoManager $seoManager;
    private readonly SiteContextInterface $siteContext;

    public function __construct(SeoManager $seoManager, SiteContextInterface $siteContext)
    {
        $this->seoManager = $seoManager;
        $this->siteContext = $siteContext;
    }

    public function generate(): SchemaType
    {
        $siteUrl = $this->siteContext->getHomeUrl() . '/';

        $org = Schema::organization()
            ->id($siteUrl . '#organization')
            ->name($this->siteContext->getBlogInfo('name'))
            ->url($siteUrl);

        $customLogoId = $this->siteContext->getThemeMod('custom_logo');

        if ($customLogoId) {
            $logoUrl = $this->siteContext->getAttachmentImageUrl((int) $customLogoId, 'full');

            if (is_string($logoUrl) && $logoUrl !== '') {
                $org->logo($logoUrl);
            }
        }

        $sameAs = $this->buildSameAs();

        if ($sameAs !== []) {
            $org->sameAs($sameAs);
        }

        return $org;
    }


    private function buildSameAs(): array
    {
        if (!$this->seoManager->hasProvider()) {
            return [];
        }

        $links = $this->seoManager->getSocialLinks();

        return array_values(array_filter($links, fn (?string $url): bool => is_string($url) && $url !== ''));
    }
}
