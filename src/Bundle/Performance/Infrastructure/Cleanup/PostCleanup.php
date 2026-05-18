<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure\Cleanup;

use BackTo\Framework\Bundle\Performance\Contracts\CleanupStrategyInterface;
use BackTo\Framework\Contracts\DatabaseConnectionInterface;

final class PostCleanup implements CleanupStrategyInterface
{
    private readonly string $status;
    private readonly string $name;

    public function __construct(string $status, string $name)
    {
        $this->status = $status;
        $this->name = $name;
    }

    public static function autoDrafts(): self
    {
        return new self('auto-draft', 'auto_drafts');
    }

    public static function trashed(): self
    {
        return new self('trash', 'trashed_posts');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function execute(DatabaseConnectionInterface $db): int
    {
        $posts = $db->prefix() . 'posts';

        return (int) $db->query(
            $db->prepare("DELETE FROM {$posts} WHERE post_status = %s", $this->status)
        );
    }
}
