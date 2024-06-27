<?php

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\ParentIdInterface;

trait HasParentId
{

    /**
     * @var int
     */
    protected $parentId = null;

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
