<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over the current authenticated user context.
 *
 * Replaces direct calls to is_user_logged_in(), get_current_user_id(),
 * and wp_update_user() in domain code, making it testable without WordPress.
 */
interface UserContextInterface
{
    /**
     * Whether a user is currently authenticated.
     */
    public function isLoggedIn(): bool;

    /**
     * Get the current authenticated user's ID (0 if not logged in).
     */
    public function getCurrentUserId(): int;

    /**
     * Update a user's role.
     */
    public function setUserRole(int $userId, string $role): void;
}
