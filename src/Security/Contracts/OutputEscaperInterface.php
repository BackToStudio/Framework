<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for output escaping.
 */
interface OutputEscaperInterface
{
    public function html(string $input): string;

    public function attr(string $input): string;

    public function url(string $input): string;

    public function js(string $input): string;

    public function textarea(string $input): string;
}
