<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Contracts;

interface ConsentCategoryInterface
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getDescription(): string;

    public function isRequired(): bool;
}
