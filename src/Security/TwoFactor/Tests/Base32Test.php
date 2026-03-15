<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Tests;

use BackTo\Framework\Security\TwoFactor\Base32;
use PHPUnit\Framework\TestCase;

class Base32Test extends TestCase
{
    /**
     * @dataProvider encodingProvider
     */
    public function testEncode(string $input, string $expected): void
    {
        $this->assertSame($expected, Base32::encode($input));
    }

    /**
     * @dataProvider encodingProvider
     */
    public function testDecode(string $expected, string $encoded): void
    {
        $this->assertSame($expected, Base32::decode($encoded));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function encodingProvider(): array
    {
        // RFC 4648 test vectors
        return [
            'empty' => ['', ''],
            'f' => ['f', 'MY'],
            'fo' => ['fo', 'MZXQ'],
            'foo' => ['foo', 'MZXW6'],
            'foob' => ['foob', 'MZXW6YQ'],
            'fooba' => ['fooba', 'MZXW6YTB'],
            'foobar' => ['foobar', 'MZXW6YTBOI'],
        ];
    }

    public function testRoundTrip(): void
    {
        $original = random_bytes(20);
        $encoded = Base32::encode($original);
        $decoded = Base32::decode($encoded);

        $this->assertSame($original, $decoded);
    }

    public function testDecodeIgnoresPadding(): void
    {
        $this->assertSame('f', Base32::decode('MY======'));
    }

    public function testDecodeCaseInsensitive(): void
    {
        $this->assertSame('foobar', Base32::decode('mzxw6ytboi'));
    }
}
