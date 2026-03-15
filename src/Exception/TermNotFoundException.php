<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception;

class TermNotFoundException extends FrameworkException
{
    public static function withId(int $id, string $taxonomy): self
    {
        return new self(sprintf('Term with ID %d in taxonomy "%s" was not found.', $id, $taxonomy));
    }
}
