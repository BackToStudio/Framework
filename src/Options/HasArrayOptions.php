<?php

namespace Fantassin\Core\WordPress\Options;

use Fantassin\Core\WordPress\Contracts\Hooks;

trait HasArrayOptions
{

    /**
     * @var array
     */
    protected $options = [];

    public function setOptions(array $options)
    {
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
     * @deprecated
     */
    public function getKey(string $key): ?string
    {
        trigger_error(
            'getKey($key) method is deprecated, you can use getString($key) or getArray($key) instead',
            E_USER_DEPRECATED
        );

        return $this->getValue($key);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function getValue(string $key)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : null;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getString(string $key): ?string
    {
        return $this->getValue($key);
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getArray(string $key): ?array
    {
        return $this->getValue($key);
    }
}

