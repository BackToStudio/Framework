<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Contracts;

use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Port for generating BreadcrumbList schema from the current page context.
 */
interface BreadcrumbSchemaGeneratorInterface
{
    public function generate(): ?SchemaType;
}
