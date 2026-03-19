<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\UserContextInterface;

/**
 * WordPress adapter for user context operations.
 */
final class WordPressUserContext implements UserContextInterface
{
    public function isLoggedIn(): bool
    {
        return function_exists('is_user_logged_in') && is_user_logged_in();
    }

    public function getCurrentUserId(): int
    {
        if (function_exists('get_current_user_id')) {
            return (int) get_current_user_id();
        }

        return 0;
    }

    public function setUserRole(int $userId, string $role): void
    {
        if (function_exists('wp_update_user')) {
            wp_update_user(['ID' => $userId, 'role' => $role]);
        }
    }
}
