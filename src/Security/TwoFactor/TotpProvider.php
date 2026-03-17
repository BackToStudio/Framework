<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor;

use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;

/**
 * TOTP implementation following RFC 6238.
 *
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6238
 * @see https://datatracker.ietf.org/doc/html/rfc4226
 */
final class TotpProvider implements TotpProviderInterface
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';

    public function generateSecret(int $length = 20): string
    {
        /** @var int<1, max> $length */
        $secret = random_bytes($length);

        return Base32::encode($secret);
    }

    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = (int) floor($timestamp / self::PERIOD);

        return $this->generateHotp($secret, $timeSlice);
    }

    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = (int) floor(time() / self::PERIOD);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $expectedCode = $this->generateHotp($secret, $currentTimeSlice + $i);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    public function getProvisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $params = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ];

        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);

        return 'otpauth://totp/' . $label . '?' . http_build_query($params);
    }

    /**
     * Generate an HOTP code (RFC 4226).
     */
    private function generateHotp(string $base32Secret, int $counter): string
    {
        $secret = Base32::decode($base32Secret);

        // Pack counter as 8-byte big-endian
        $counterBytes = pack('N*', 0, $counter);

        // HMAC-SHA1
        $hash = hash_hmac(self::ALGORITHM, $counterBytes, $secret, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $binary =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }
}
