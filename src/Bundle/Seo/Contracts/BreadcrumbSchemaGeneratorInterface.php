<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Contracts;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

/**
 * Port for generating BreadcrumbList schema from the current page context.
 */
interface BreadcrumbSchemaGeneratorInterface
{
    public function generate(): ?SchemaType;
}
