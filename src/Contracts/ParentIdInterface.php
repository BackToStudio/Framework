<?php

namespace Fantassin\Core\WordPress\Contracts;

interface ParentIdInterface
{
    /**
     * @return int|null
     */
    public function getParentId(): ?int;

    /**
     * @param int $id
     * @return ParentIdInterface
     */
    public function setParentId(int $id): ParentIdInterface;

}
