<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Generator;

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Bundle\Seo\Infrastructure\WordPressBreadcrumbSchemaGenerator;

/**
 * @deprecated Use BreadcrumbSchemaGeneratorInterface instead. This class will be removed in a future version.
 */
class BreadcrumbSchemaGenerator extends WordPressBreadcrumbSchemaGenerator implements BreadcrumbSchemaGeneratorInterface
{
}
