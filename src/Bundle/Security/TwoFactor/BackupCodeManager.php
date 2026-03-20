<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor;

use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\BackupCodeManagerInterface;

final class BackupCodeManager implements BackupCodeManagerInterface
{
    private const CODE_LENGTH = 8;

    
    public function generate(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateSingleCode();
        }

        return $codes;
    }

    public function hash(string $code): string
    {
        return password_hash($this->normalizeCode($code), PASSWORD_BCRYPT);
    }

    
    public function verify(string $code, array $hashedCodes): bool
    {
        return $this->findMatchingIndex($code, $hashedCodes) !== null;
    }

    
    public function findMatchingIndex(string $code, array $hashedCodes): ?int
    {
        $normalized = $this->normalizeCode($code);
        $matchedIndex = null;

        // Iterate all codes to prevent timing side-channel leaks
        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($normalized, $hashedCode)) {
                $matchedIndex = $index;
            }
        }

        return $matchedIndex;
    }

    private function generateSingleCode(): string
    {
        $bytes = random_bytes(self::CODE_LENGTH);
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= (string) (ord($bytes[$i]) % 10);
        }

        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }

    private function normalizeCode(string $code): string
    {
        return str_replace(['-', ' '], '', trim($code));
    }
}
