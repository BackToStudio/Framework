<?php

use Isolated\Symfony\Component\Finder\Finder;

return array(
    'finders'  => [
        Finder::create()
              ->files()
              ->in('vendor/psr/log/src')
              ->name('*.php')
    ],
    'patchers' => [],
);
