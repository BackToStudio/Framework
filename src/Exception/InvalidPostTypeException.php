<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception;

final class InvalidPostTypeException extends FrameworkException
{
    public static function emptyKey(): self
    {
        return new self(
            'WordPress required post type name. (max. 20 characters, cannot contain capital letters, underscores or spaces)'
        );
    }
}
