<?php

declare(strict_types=1);

namespace BackTo\Framework\Options;

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

/**
 * @deprecated Use OptionsRepositoryInterface with WordPressOptionsRepository instead.
 */
class OptionsRepository
{
    private OptionsRepositoryInterface $repository;

    public function __construct(OptionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function find(string $key): mixed
    {
        return $this->repository->get($key, []);
    }
}
