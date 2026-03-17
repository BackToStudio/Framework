<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

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
    private SeoManager $seoManager;

    public function __construct(SeoManager $seoManager)
    {
        $this->seoManager = $seoManager;
    }

    public function generate(): SchemaType
    {
        $siteUrl = \home_url('/');

        $org = Schema::organization()
            ->id($siteUrl . '#organization')
            ->name(\get_bloginfo('name'))
            ->url($siteUrl);

        $customLogoId = \get_theme_mod('custom_logo');

        if ($customLogoId) {
            $logoUrl = \wp_get_attachment_image_url($customLogoId, 'full');

            if (\is_string($logoUrl) && $logoUrl !== '') {
                $org->logo($logoUrl);
            }
        }

        $sameAs = $this->buildSameAs();

        if ($sameAs !== []) {
            $org->sameAs($sameAs);
        }

        return $org;
    }

    /**
     * @return string[]
     */
    private function buildSameAs(): array
    {
        if (!$this->seoManager->hasProvider()) {
            return [];
        }

        $links = $this->seoManager->getSocialLinks();

        return array_values(array_filter($links, fn (?string $url): bool => \is_string($url) && $url !== ''));
    }
}
