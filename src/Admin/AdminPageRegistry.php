<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Contracts\RegistryInterface;

class AdminPageRegistry implements RegistryInterface
{
    /** @var AdminPageInterface[] */
    private array $pages = [];

    public function add(AdminPageInterface $page): self
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * @return AdminPageInterface[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }
}
