<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\QueryContextInterface;

class WordPressQueryContext implements QueryContextInterface
{
    public function is404(): bool
    {
        return function_exists('is_404') && \is_404();
    }

    public function isSearch(): bool
    {
        return function_exists('is_search') && \is_search();
    }

    public function isSingular(): bool
    {
        return function_exists('is_singular') && \is_singular();
    }
}
