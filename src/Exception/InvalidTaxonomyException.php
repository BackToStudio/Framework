<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception;

class InvalidTaxonomyException extends FrameworkException
{
    public static function emptyKey(): self
    {
        return new self('WordPress requires a taxonomy key.');
    }

}
