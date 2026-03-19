<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Generates a WebSite schema from WordPress site settings.
 *
 * Produces a WebSite node with @id "#website", linked to "#organization" as publisher,
 * and a SearchAction for the sitelinks search box.
 */
class WebSiteSchemaGenerator
{
    private readonly SiteContextInterface $siteContext;

    public function __construct(SiteContextInterface $siteContext)
    {
        $this->siteContext = $siteContext;
    }

    public function generate(): SchemaType
    {
        $siteUrl = $this->siteContext->getHomeUrl() . '/';

        return Schema::webSite()
            ->id($siteUrl . '#website')
            ->name($this->siteContext->getBlogInfo('name'))
            ->url($siteUrl)
            ->description($this->siteContext->getBlogInfo('description'))
            ->set('publisher', Schema::ref($siteUrl . '#organization'))
            ->potentialAction(
                Schema::searchAction()
                    ->target($siteUrl . '?s={search_term_string}')
                    ->queryInput('required name=search_term_string')
            );
    }
}
