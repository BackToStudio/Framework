<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin\Infrastructure;

use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;

use function current_user_can;
use function get_role;
use function remove_submenu_page;

/**
 * WordPress adapter for role and capability management.
 */
final class WordPressCapabilityManager implements CapabilityManagerInterface
{
    public function addCapToRole(string $role, string $capability): void
    {
        $roleObject = get_role($role);

        if ($roleObject !== null) {
            $roleObject->add_cap($capability);
        }
    }

    public function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }

    public function removeSubmenuPage(string $parentSlug, string $menuSlug): void
    {
        remove_submenu_page($parentSlug, $menuSlug);
    }
}
