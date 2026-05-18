<?php

use Isolated\Symfony\Component\Finder\Finder;

return array(
    'finders'  => [
        Finder::create()
              ->files()
              ->in('vendor/psr/event-dispatcher')
              ->name('*.php')
    ],
    'patchers' => [],
);
