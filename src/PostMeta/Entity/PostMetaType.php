<?php

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\Compose\Type;

abstract class PostMetaType
{
    const STRING = Type::STRING;
    const BOOLEAN = Type::BOOLEAN;
    const INTEGER = Type::INTEGER;
    const NUMBER = Type::NUMBER;
    const ARRAY = Type::ARRAY;
    const OBJECT = Type::OBJECT;
}