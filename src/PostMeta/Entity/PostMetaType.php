<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\Compose\Type;

/**
 * @deprecated Use BackTo\Framework\Compose\Type enum directly.
 */
class PostMetaType
{
    public const STRING = 'string';
    public const BOOLEAN = 'boolean';
    public const INTEGER = 'integer';
    public const NUMBER = 'number';
    public const ARRAY = 'array';
    public const OBJECT = 'object';
}
