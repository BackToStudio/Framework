<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress conditional query tags.
 *
 * Replaces direct calls to is_404(), is_search(), is_singular(), etc.
 * in domain code, making it testable without WordPress.
 */
interface QueryContextInterface
{
    public function isAdmin(): bool;

    public function is404(): bool;

    public function isSearch(): bool;

    public function isSingular(): bool;

    /**
     * Get the currently queried object (equivalent to get_queried_object()).
     */
    public function getQueriedObject(): ?object;
}
