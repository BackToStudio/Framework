<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin\Tests;

use BackTo\Framework\Bundle\Admin\AdminPageRegistry;
use BackTo\Framework\Contracts\AdminPageInterface;
use BackTo\Framework\Contracts\RegistryInterface;
use PHPUnit\Framework\TestCase;

class AdminPageRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new AdminPageRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new AdminPageRegistry();
        $this->assertCount(0, $registry->getPages());
    }

    public function testAddPage(): void
    {
        $registry = new AdminPageRegistry();
        $page = $this->createMock(AdminPageInterface::class);

        $result = $registry->add($page);

        $this->assertSame($registry, $result);
        $this->assertCount(1, $registry->getPages());
        $this->assertSame($page, $registry->getPages()[0]);
    }

    public function testAddMultiplePages(): void
    {
        $registry = new AdminPageRegistry();
        $page1 = $this->createMock(AdminPageInterface::class);
        $page2 = $this->createMock(AdminPageInterface::class);

        $registry->add($page1);
        $registry->add($page2);

        $this->assertCount(2, $registry->getPages());
    }
}
