<?php

use Isolated\Symfony\Component\Finder\Finder;

return array(
    'finders'  => [
        Finder::create()
              ->files()
              ->in('vendor/symfony/cache-contracts')
              ->name('*.php')
    ],
    'patchers' => [],
);
