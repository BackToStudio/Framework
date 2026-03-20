<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin\Infrastructure;

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;

use function add_menu_page;
use function add_submenu_page;

/**
 * WordPress adapter for admin page registration.
 */
final class WordPressAdminPageRegistrar implements AdminPageRegistrarInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function registerMenuPage(array $args): void
    {
        add_menu_page(
            $args['page_title'] ?? '',
            $args['menu_title'] ?? '',
            $args['capability'] ?? 'manage_options',
            $args['menu_slug'] ?? '',
            $args['callback'] ?? '',
            $args['icon_url'] ?? '',
            $args['position'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    public function registerSubmenuPage(string $parentSlug, array $args): void
    {
        add_submenu_page(
            $parentSlug,
            $args['page_title'] ?? '',
            $args['menu_title'] ?? '',
            $args['capability'] ?? 'manage_options',
            $args['menu_slug'] ?? '',
            $args['callback'] ?? '',
            $args['position'] ?? null,
        );
    }
}
