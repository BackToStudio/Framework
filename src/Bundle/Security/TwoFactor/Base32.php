<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor;

/**
 * Base32 encoder/decoder for TOTP secret keys.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc4648
 */
final class Base32
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        $chunks = str_split($binary, 5);

        foreach ($chunks as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::ALPHABET[(int) bindec($chunk)];
        }

        return $encoded;
    }

    public static function decode(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }

        $encoded = strtoupper(rtrim($encoded, '='));
        $binary = '';

        foreach (str_split($encoded) as $char) {
            $index = strpos(self::ALPHABET, $char);

            if ($index === false) {
                throw new \InvalidArgumentException(\sprintf('Invalid Base32 character: "%s".', $char));
            }

            $binary .= str_pad(decbin((int) $index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        $chunks = str_split($binary, 8);

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }
            $decoded .= chr((int) bindec($chunk));
        }

        return $decoded;
    }
}
