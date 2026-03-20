<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\NonceManagerInterface;

final class WordPressNonceManager implements NonceManagerInterface
{
    public function createNonce(string $action): string
    {
        return function_exists('wp_create_nonce') ? \wp_create_nonce($action) : '';
    }

    public function verifyNonce(string $nonce, string $action): bool
    {
        if (function_exists('wp_verify_nonce')) {
            return \wp_verify_nonce($nonce, $action) !== false;
        }

        return false;
    }
}
