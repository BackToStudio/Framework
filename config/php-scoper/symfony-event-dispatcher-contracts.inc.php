<?php

use Isolated\Symfony\Component\Finder\Finder;

return array(
    'finders'  => [
        Finder::create()
              ->files()
              ->in('vendor/symfony/event-dispatcher-contracts')
              ->name('*.php')
    ],
    'patchers' => [],
);
