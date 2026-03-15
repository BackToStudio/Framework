<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Contracts;

use BackTo\Framework\Contracts\HookInterface;

/**
 * Represents an admin menu page to be registered.
 */
interface AdminPageInterface extends HookInterface
{
    public function getPageTitle(): string;

    public function getMenuTitle(): string;

    public function getCapability(): string;

    public function getMenuSlug(): string;

    public function getIconUrl(): string;

    public function getPosition(): ?int;

    public function render(): void;
}
