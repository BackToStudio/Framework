<?php

namespace Fantassin\Core\WordPress\Contracts;

interface IdInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int $id
     * @return IdInterface
     */
    public function setId(int $id): IdInterface;

}
