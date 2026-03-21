<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\Headers\CorsOriginValidator;
use PHPUnit\Framework\TestCase;

class CorsOriginValidatorTest extends TestCase
{
    private CorsOriginValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CorsOriginValidator();
    }

    public function testNoOriginsConfiguredReturnsFalse(): void
    {
        $this->assertFalse($this->validator->isOriginAllowed('https://example.com'));
    }

    public function testAllowedOriginReturnsTrue(): void
    {
        $this->validator->addAllowedOrigin('https://example.com');

        $this->assertTrue($this->validator->isOriginAllowed('https://example.com'));
    }

    public function testDisallowedOriginReturnsFalse(): void
    {
        $this->validator->addAllowedOrigin('https://example.com');

        $this->assertFalse($this->validator->isOriginAllowed('https://evil.com'));
    }

    public function testWildcardAllowsAnyOrigin(): void
    {
        $this->validator->addAllowedOrigin('*');

        $this->assertTrue($this->validator->isOriginAllowed('https://anything.com'));
    }

    public function testAddAllowedOriginDeduplicates(): void
    {
        $this->validator->addAllowedOrigin('https://example.com');
        $this->validator->addAllowedOrigin('https://example.com');

        $this->assertSame(['https://example.com'], $this->validator->getAllowedOrigins());
    }

    public function testAddAllowedOriginAcceptsArray(): void
    {
        $this->validator->addAllowedOrigin(['https://a.com', 'https://b.com']);

        $this->assertSame(['https://a.com', 'https://b.com'], $this->validator->getAllowedOrigins());
    }

    public function testWildcardOriginWithCredentialsThrows(): void
    {
        $this->validator->addAllowedOrigin('*');

        $this->expectException(\InvalidArgumentException::class);
        $this->validator->setAllowCredentials(true);
    }

    public function testCredentialsWithWildcardOriginThrows(): void
    {
        $this->validator->setAllowCredentials(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->validator->addAllowedOrigin('*');
    }

    public function testSetAllowCredentials(): void
    {
        $this->assertFalse($this->validator->isAllowCredentials());

        $this->validator->setAllowCredentials(true);

        $this->assertTrue($this->validator->isAllowCredentials());
    }
}
