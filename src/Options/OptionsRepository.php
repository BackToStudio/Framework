<?php

namespace Fantassin\Core\WordPress\Options;

class OptionsRepository
{

    /**
     * @param string $key
     * @return array
     */
    public function find(string $key)
    {
        return \get_option($key, []);
    }

}

