<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Repository;

use BackTo\Framework\Taxonomy\Contracts\TermRepositoryInterface;
use BackTo\Framework\Taxonomy\Infrastructure\WordPressTermRepository;

/**
 * @deprecated Use TermRepositoryInterface instead. This class will be removed in a future version.
 */
final class TermRepository extends WordPressTermRepository implements TermRepositoryInterface
{
}
