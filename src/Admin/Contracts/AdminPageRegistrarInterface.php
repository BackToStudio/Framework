<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Contracts;

/**
 * Port interface for admin menu page registration.
 */
interface AdminPageRegistrarInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function registerMenuPage(array $args): void;

    /**
     * @param array<string, mixed> $args
     */
    public function registerSubmenuPage(string $parentSlug, array $args): void;
}
