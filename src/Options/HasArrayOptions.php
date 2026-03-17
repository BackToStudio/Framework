<?php

declare(strict_types=1);

namespace BackTo\Framework\Options;

trait HasArrayOptions
{
    /** @var array<string, mixed> */
    protected array $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function isInOptions(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    protected function getValue(string $key): mixed
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : null;
    }

    public function getString(string $key): ?string
    {
        return $this->getValue($key);
    }

    /**
     * @return array<mixed>|null
     */
    public function getArray(string $key): ?array
    {
        return $this->getValue($key);
    }
}
