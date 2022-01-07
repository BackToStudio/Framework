<?php

namespace Fantassin\Core\WordPress\Compose;

use Fantassin\Core\WordPress\Contracts\ParentIdInterface;

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
