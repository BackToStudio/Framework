<?php

declare(strict_types=1);

namespace BackTo\Framework\Options;

class OptionsRepository
{
    /**
     * @return mixed
     */
    public function find(string $key): mixed
    {
        return \get_option($key, []);
    }
}
