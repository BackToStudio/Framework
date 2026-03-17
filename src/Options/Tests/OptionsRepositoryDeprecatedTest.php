<?php

declare(strict_types=1);

namespace BackTo\Framework\Options\Tests;

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Options\OptionsRepository;
use PHPUnit\Framework\TestCase;

class OptionsRepositoryDeprecatedTest extends TestCase
{
    public function testFindDelegatesToInterfaceGet(): void
    {
        $mock = $this->createMock(OptionsRepositoryInterface::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('my_key', [])
            ->willReturn(['value1', 'value2']);

        $repo = new OptionsRepository($mock);
        $result = $repo->find('my_key');

        $this->assertSame(['value1', 'value2'], $result);
    }

    public function testFindPassesEmptyArrayAsDefault(): void
    {
        $mock = $this->createMock(OptionsRepositoryInterface::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('missing', [])
            ->willReturn([]);

        $repo = new OptionsRepository($mock);
        $result = $repo->find('missing');

        $this->assertSame([], $result);
    }

    public function testFindReturnsWhateverRepositoryReturns(): void
    {
        $mock = $this->createMock(OptionsRepositoryInterface::class);
        $mock->method('get')->willReturn('not-an-array');

        $repo = new OptionsRepository($mock);

        // find() returns mixed, so even if underlying returns string, it passes through
        $this->assertSame('not-an-array', $repo->find('key'));
    }

    public function testFindWithEmptyKey(): void
    {
        $mock = $this->createMock(OptionsRepositoryInterface::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('', [])
            ->willReturn([]);

        $repo = new OptionsRepository($mock);
        $this->assertSame([], $repo->find(''));
    }

    public function testFindReturnsNullWhenRepositoryReturnsNull(): void
    {
        $mock = $this->createMock(OptionsRepositoryInterface::class);
        $mock->method('get')->willReturn(null);

        $repo = new OptionsRepository($mock);
        $this->assertNull($repo->find('key'));
    }
}
