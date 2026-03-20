<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin\Contracts;

/**
 * Port interface for WordPress role and capability management.
 *
 * Abstracts get_role, add_cap, current_user_can, remove_submenu_page
 * so that application-layer code does not call WordPress functions directly.
 */
interface CapabilityManagerInterface
{
    public function addCapToRole(string $role, string $capability): void;

    public function currentUserCan(string $capability): bool;

    public function removeSubmenuPage(string $parentSlug, string $menuSlug): void;
}
