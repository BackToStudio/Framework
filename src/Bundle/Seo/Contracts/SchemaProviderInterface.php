<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Contracts;

use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

/**
 * Provides schema.org structured data from an SEO plugin.
 *
 * Implementations should register their schemas on the SchemaManager.
 */
interface SchemaProviderInterface
{
    /**
     * Register schema.org structured data on the manager.
     */
    public function registerSchemas(SchemaManager $schemaManager): void;
}
