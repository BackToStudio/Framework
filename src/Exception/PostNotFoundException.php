<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception;

final class PostNotFoundException extends FrameworkException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Post with ID %d was not found.', $id));
    }
}
