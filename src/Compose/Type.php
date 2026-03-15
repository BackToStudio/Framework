<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

enum Type: string
{
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case NUMBER = 'number';
    case ARRAY = 'array';
    case OBJECT = 'object';
}
