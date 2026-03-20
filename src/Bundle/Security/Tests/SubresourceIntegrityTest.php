<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Contracts\SubresourceIntegrityInterface;
use BackTo\Framework\Bundle\Security\Headers\SubresourceIntegrity;
use PHPUnit\Framework\TestCase;

class SubresourceIntegrityTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private SubresourceIntegrity $sri;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->sri = new SubresourceIntegrity($this->dispatcher);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->sri);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->sri);
        $this->assertInstanceOf(SubresourceIntegrityInterface::class, $this->sri);
    }

    public function testGetName(): void
    {
        $this->assertSame('subresource_integrity', $this->sri->getName());
    }

    public function testHooksRegistersFilters(): void
    {
        $filters = [];
        $this->dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $filter) use (&$filters) {
                $filters[] = $filter;
            });

        $this->sri->hooks();

        $this->assertSame(['script_loader_tag', 'style_loader_tag'], $filters);
    }

    public function testRegisterHash(): void
    {
        $this->sri->registerHash('jquery', 'sha384-abc123');

        $this->assertSame('sha384-abc123', $this->sri->getHash('jquery'));
    }

    public function testGetHashReturnsNullForUnregistered(): void
    {
        $this->assertNull($this->sri->getHash('unknown'));
    }

    public function testAddIntegrityToScript(): void
    {
        $this->sri->registerHash('jquery', 'sha384-abc123');

        $tag = '<script src="https://cdn.example.com/jquery.min.js"></script>';
        $result = $this->sri->addIntegrityToScript($tag, 'jquery');

        $this->assertStringContainsString('integrity="sha384-abc123"', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
    }

    public function testAddIntegrityToStyle(): void
    {
        $this->sri->registerHash('bootstrap-css', 'sha384-def456');

        $tag = '<link rel="stylesheet" href="https://cdn.example.com/bootstrap.min.css" />';
        $result = $this->sri->addIntegrityToStyle($tag, 'bootstrap-css');

        $this->assertStringContainsString('integrity="sha384-def456"', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
    }

    public function testDoesNotModifyUnregisteredHandle(): void
    {
        $tag = '<script src="https://cdn.example.com/app.js"></script>';
        $result = $this->sri->addIntegrityToScript($tag, 'app');

        $this->assertSame($tag, $result);
    }

    public function testDoesNotDuplicateIntegrityAttribute(): void
    {
        $this->sri->registerHash('jquery', 'sha384-abc123');

        $tag = '<script integrity="sha384-existing" src="https://cdn.example.com/jquery.min.js"></script>';
        $result = $this->sri->addIntegrityToScript($tag, 'jquery');

        $this->assertSame($tag, $result);
    }

    public function testGetRegisteredHashes(): void
    {
        $this->sri->registerHash('jquery', 'sha384-abc');
        $this->sri->registerHash('lodash', 'sha256-def');

        $hashes = $this->sri->getRegisteredHashes();

        $this->assertSame([
            'jquery' => 'sha384-abc',
            'lodash' => 'sha256-def',
        ], $hashes);
    }

    public function testFluentInterface(): void
    {
        $result = $this->sri
            ->registerHash('a', 'sha384-aaa')
            ->registerHash('b', 'sha384-bbb');

        $this->assertSame($this->sri, $result);
    }
}
