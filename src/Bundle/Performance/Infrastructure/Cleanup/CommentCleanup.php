<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

final class CommentCleanup implements CleanupStrategyInterface
{
    private readonly string $status;
    private readonly string $name;

    public function __construct(string $status, string $name)
    {
        $this->status = $status;
        $this->name = $name;
    }

    public static function spam(): self
    {
        return new self('spam', 'spam_comments');
    }

    public static function trashed(): self
    {
        return new self('trash', 'trashed_comments');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function execute(DatabaseConnectionInterface $db): int
    {
        $comments = $db->prefix() . 'comments';

        return (int) $db->query(
            $db->prepare("DELETE FROM {$comments} WHERE comment_approved = %s", $this->status)
        );
    }
}
