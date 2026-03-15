<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Performance\Infrastructure\WordPressHtmlOptimizer;
use PHPUnit\Framework\TestCase;

class WordPressHtmlOptimizerTest extends TestCase
{
    private WordPressHtmlOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new WordPressHtmlOptimizer();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(HtmlOptimizerInterface::class, $this->optimizer);
    }

    public function testReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', $this->optimizer->optimize(''));
    }

    public function testRemovesHtmlComments(): void
    {
        $html = '<div><!-- This is a comment -->content</div>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('<!-- This is a comment -->', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testPreservesIeConditionalComments(): void
    {
        $html = '<!--[if IE 9]><link rel="stylesheet" href="ie9.css"><![endif]-->';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('<!--[if IE 9]>', $result);
    }

    public function testCollapsesWhitespace(): void
    {
        $html = "<div>   \n\n   content   \n   </div>";
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString("\n", $result);
    }

    public function testRemovesTypeAttributes(): void
    {
        $html = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('type="text/javascript"', $result);
    }

    public function testPreservesPreContent(): void
    {
        $html = '<pre>   preserved   whitespace   </pre>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('   preserved   whitespace   ', $result);
    }

    public function testPreservesScriptContent(): void
    {
        $html = '<script>var x = "  hello  ";</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('var x = "  hello  "', $result);
    }

    public function testCollapsesWhitespaceBetweenTags(): void
    {
        $html = '<div>    </div>    <p>text</p>';
        $result = $this->optimizer->optimize($html);

        $this->assertSame('<div> </div> <p>text</p>', $result);
    }
}
