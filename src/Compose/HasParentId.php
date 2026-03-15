<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\ParentIdInterface;

trait HasParentId
{
    protected ?int $parentId = null;

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): ParentIdInterface
    {
        $this->parentId = $parentId;
        return $this;
    }
}
