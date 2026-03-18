<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface ParentIdInterface
{
    
    public function getParentId(): ?int;

    
    public function setParentId(int $id): ParentIdInterface;

}
