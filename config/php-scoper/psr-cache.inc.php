<?php

use Isolated\Symfony\Component\Finder\Finder;

return array(
    'finders'  => [
        Finder::create()
              ->files()
              ->in('vendor/psr/cache/src')
              ->name('*.php')
    ],
    'patchers' => [],
);
