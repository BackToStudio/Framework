<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Contracts;

/**
 * Port interface for CLI output.
 *
 * Abstracts WP_CLI::success() / WP_CLI::error() so that
 * command classes do not depend directly on WP_CLI.
 */
interface CliOutputInterface
{
    public function success(string $message): void;

    public function error(string $message): void;
}
