<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

trait TextDomain
{
    protected string $textDomain = '';

    public function getTextDomain(): string
    {
        return $this->textDomain;
    }

    public function setTextDomain(string $textDomain): self
    {
        $this->textDomain = $textDomain;

        return $this;
    }
}
