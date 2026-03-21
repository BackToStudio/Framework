<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\Headers\CspDirectiveBuilder;
use PHPUnit\Framework\TestCase;

class CspDirectiveBuilderTest extends TestCase
{
    private CspDirectiveBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CspDirectiveBuilder();
    }

    public function testDefaultDirectivesIncludeSelf(): void
    {
        $directives = $this->builder->getDirectives();

        $this->assertArrayHasKey('default-src', $directives);
        $this->assertContains("'self'", $directives['default-src']);
    }

    public function testAddDirectiveString(): void
    {
        $this->builder->addDirective('script-src', 'https://cdn.example.com');

        $this->assertContains('https://cdn.example.com', $this->builder->getDirectives()['script-src']);
    }

    public function testAddDirectiveArray(): void
    {
        $this->builder->addDirective('img-src', ['https://a.com', 'https://b.com']);

        $directives = $this->builder->getDirectives();
        $this->assertContains('https://a.com', $directives['img-src']);
        $this->assertContains('https://b.com', $directives['img-src']);
    }

    public function testAddDirectiveNoDuplicates(): void
    {
        $this->builder->addDirective('default-src', "'self'");

        $count = array_count_values($this->builder->getDirectives()['default-src']);
        $this->assertSame(1, $count["'self'"]);
    }

    public function testAddNewDirective(): void
    {
        $this->builder->addDirective('report-uri', '/csp-report');

        $this->assertArrayHasKey('report-uri', $this->builder->getDirectives());
        $this->assertContains('/csp-report', $this->builder->getDirectives()['report-uri']);
    }

    public function testBuildHeaderValueWithoutNonce(): void
    {
        $header = $this->builder->buildHeaderValue();

        $this->assertStringContainsString("default-src 'self'", $header);
        $this->assertStringContainsString("script-src 'self'", $header);
        $this->assertStringContainsString('; ', $header);
    }

    public function testBuildHeaderValueWithNonce(): void
    {
        $header = $this->builder->buildHeaderValue('abc123');

        $this->assertStringContainsString("'nonce-abc123'", $header);
    }

    public function testFluentInterface(): void
    {
        $result = $this->builder->addDirective('script-src', 'https://cdn.example.com');

        $this->assertSame($this->builder, $result);
    }
}
