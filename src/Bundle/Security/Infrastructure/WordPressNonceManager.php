<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Infrastructure;

use BackTo\Framework\Bundle\Security\Contracts\NonceManagerInterface;

use function wp_create_nonce;
use function wp_verify_nonce;

/**
 * WordPress adapter for nonce management.
 */
final class WordPressNonceManager implements NonceManagerInterface
{
    private const FIELD_NAME = '_backto_nonce';

    public function create(string $action): string
    {
        return wp_create_nonce($action);
    }

    public function verify(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    public function getFieldName(): string
    {
        return self::FIELD_NAME;
    }
}
