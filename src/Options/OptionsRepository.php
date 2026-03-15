<?php

declare(strict_types=1);

namespace BackTo\Framework\Options;

class OptionsRepository
{
    /**
     * @return array<string, mixed>
     */
    public function find(string $key): array
    {
        return \get_option($key, []);
    }
}
