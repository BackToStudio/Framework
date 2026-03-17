<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Generates a WebSite schema from WordPress site settings.
 *
 * Produces a WebSite node with a SearchAction (sitelinks search box).
 */
class WebSiteSchemaGenerator
{
    public function generate(): SchemaType
    {
        $siteUrl = \home_url('/');

        return Schema::webSite()
            ->name(\get_bloginfo('name'))
            ->url($siteUrl)
            ->description(\get_bloginfo('description'))
            ->potentialAction(
                Schema::searchAction()
                    ->target($siteUrl . '?s={search_term_string}')
                    ->queryInput('required name=search_term_string')
            );
    }
}
