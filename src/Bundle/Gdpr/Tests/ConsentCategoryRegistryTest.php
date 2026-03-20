<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Bundle\Gdpr\ConsentCategoryRegistry;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;
use PHPUnit\Framework\TestCase;

class ConsentCategoryRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new ConsentCategoryRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new ConsentCategoryRegistry();
        $this->assertCount(0, $registry->getCategories());
    }

    public function testAddCategories(): void
    {
        $registry = new ConsentCategoryRegistry();

        $analytics = new ConsentCategory('analytics', 'Analytique');
        $marketing = new ConsentCategory('marketing', 'Marketing');

        $registry->add($analytics);
        $registry->add($marketing);

        $this->assertCount(2, $registry->getCategories());
    }

    public function testGetByKey(): void
    {
        $registry = new ConsentCategoryRegistry();
        $analytics = new ConsentCategory('analytics', 'Analytique');
        $registry->add($analytics);

        $result = $registry->get('analytics');

        $this->assertInstanceOf(ConsentCategoryInterface::class, $result);
        $this->assertSame('analytics', $result->getKey());
    }

    public function testGetByKeyReturnsNullForUnknownKey(): void
    {
        $registry = new ConsentCategoryRegistry();
        $this->assertNull($registry->get('unknown'));
    }

    public function testFluentInterface(): void
    {
        $registry = new ConsentCategoryRegistry();
        $result = $registry->add(new ConsentCategory('a', 'A'));

        $this->assertSame($registry, $result);
    }
}
