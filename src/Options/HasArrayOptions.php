<?php

namespace Fantassin\Core\WordPress\Options;

use Fantassin\Core\WordPress\Contracts\Hooks;

trait HasArrayOptions {

    /**
     * @var array
     */
    protected $options = [];

    public function setOptions( array $options ){
        $this->options = $options;
    }


    /**
     * @param string $key
     * @return bool
     */
    public function isInOptions(string $key): bool
    {
        return in_array($key, $this->options);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getKey(string $key): ?string
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : null;
    }
}

