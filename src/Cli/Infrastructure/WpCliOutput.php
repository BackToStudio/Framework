<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Infrastructure;

use BackTo\Framework\Cli\Contracts\CliOutputInterface;

/**
 * WordPress CLI adapter for command output.
 */
final class WpCliOutput implements CliOutputInterface
{
    public function success(string $message): void
    {
        if (\class_exists('WP_CLI')) {
            \WP_CLI::success($message);
        }
    }

    public function error(string $message): void
    {
        if (\class_exists('WP_CLI')) {
            \WP_CLI::error($message);
        }
    }
}
