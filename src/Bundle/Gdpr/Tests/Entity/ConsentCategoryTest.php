<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests\Entity;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;
use PHPUnit\Framework\TestCase;

class ConsentCategoryTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $category = new ConsentCategory('analytics', 'Analytique');
        $this->assertInstanceOf(ConsentCategoryInterface::class, $category);
    }

    public function testGetters(): void
    {
        $category = new ConsentCategory('analytics', 'Analytique', 'Mesure d\'audience', false);

        $this->assertSame('analytics', $category->getKey());
        $this->assertSame('Analytique', $category->getLabel());
        $this->assertSame('Mesure d\'audience', $category->getDescription());
        $this->assertFalse($category->isRequired());
    }

    public function testRequiredCategory(): void
    {
        $category = new ConsentCategory('necessary', 'Necessaire', 'Cookies essentiels', true);

        $this->assertTrue($category->isRequired());
    }

    public function testDefaultValues(): void
    {
        $category = new ConsentCategory('marketing', 'Marketing');

        $this->assertSame('', $category->getDescription());
        $this->assertFalse($category->isRequired());
    }
}
