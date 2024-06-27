<?php

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\IdInterface;

trait HasId
{

    /**
     * @var int
     */
    protected $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): IdInterface
    {
        $this->id = $id;
        return $this;
    }

}
