<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface IdInterface
{
    
    public function getId(): ?int;

    
    public function setId(int $id): IdInterface;

}
