<?php

namespace BackTo\Framework\Contracts;

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
