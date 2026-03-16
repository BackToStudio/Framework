<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Tests;

use BackTo\Framework\Security\TwoFactor\BackupCodeManager;
use BackTo\Framework\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use PHPUnit\Framework\TestCase;

class BackupCodeManagerTest extends TestCase
{
    private BackupCodeManager $manager;

    protected function setUp(): void
    {
        $this->manager = new BackupCodeManager();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(BackupCodeManagerInterface::class, $this->manager);
    }

    public function testGenerateDefaultCount(): void
    {
        $codes = $this->manager->generate();

        $this->assertCount(8, $codes);
    }

    public function testGenerateCustomCount(): void
    {
        $codes = $this->manager->generate(4);

        $this->assertCount(4, $codes);
    }

    public function testGenerateFormat(): void
    {
        $codes = $this->manager->generate();

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{4}$/', $code);
        }
    }

    public function testGenerateUniqueness(): void
    {
        $codes = $this->manager->generate(100);

        $this->assertSame(count($codes), count(array_unique($codes)));
    }

    public function testHashReturnsString(): void
    {
        $hash = $this->manager->hash('1234-5678');

        $this->assertNotEmpty($hash);
        $this->assertNotSame('1234-5678', $hash);
    }

    public function testVerifyWithCorrectCode(): void
    {
        $code = '1234-5678';
        $hash = $this->manager->hash($code);

        $this->assertTrue($this->manager->verify($code, [$hash]));
    }

    public function testVerifyWithWrongCode(): void
    {
        $hash = $this->manager->hash('1234-5678');

        $this->assertFalse($this->manager->verify('9999-9999', [$hash]));
    }

    public function testVerifyIgnoresDashes(): void
    {
        $hash = $this->manager->hash('1234-5678');

        $this->assertTrue($this->manager->verify('12345678', [$hash]));
    }

    public function testVerifyIgnoresSpaces(): void
    {
        $hash = $this->manager->hash('1234-5678');

        $this->assertTrue($this->manager->verify('1234 5678', [$hash]));
    }

    public function testFindMatchingIndex(): void
    {
        $codes = $this->manager->generate(3);
        $hashes = array_map(fn (string $c): string => $this->manager->hash($c), $codes);

        $index = $this->manager->findMatchingIndex($codes[1], $hashes);

        $this->assertSame(1, $index);
    }

    public function testFindMatchingIndexReturnsNullWhenNotFound(): void
    {
        $hash = $this->manager->hash('1234-5678');

        $this->assertNull($this->manager->findMatchingIndex('0000-0000', [$hash]));
    }

    public function testFullWorkflow(): void
    {
        $codes = $this->manager->generate();
        $hashes = array_map(fn (string $c): string => $this->manager->hash($c), $codes);

        // Use first code
        $index = $this->manager->findMatchingIndex($codes[0], $hashes);
        $this->assertSame(0, $index);

        // Remove it
        unset($hashes[$index]);
        $hashes = array_values($hashes);

        // First code no longer valid
        $this->assertFalse($this->manager->verify($codes[0], $hashes));

        // Second code still valid
        $this->assertTrue($this->manager->verify($codes[1], $hashes));
    }
}
