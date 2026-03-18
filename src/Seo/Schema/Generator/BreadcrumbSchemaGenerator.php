<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Seo\Infrastructure\WordPressBreadcrumbSchemaGenerator;

/**
 * @deprecated Use BreadcrumbSchemaGeneratorInterface instead. This class will be removed in a future version.
 */
class BreadcrumbSchemaGenerator extends WordPressBreadcrumbSchemaGenerator implements BreadcrumbSchemaGeneratorInterface
{
}
