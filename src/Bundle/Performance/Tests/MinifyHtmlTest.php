<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Hooks\Html\MinifyHtml;
use PHPUnit\Framework\TestCase;

class MinifyHtmlTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private HtmlOptimizerInterface $htmlOptimizer;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->htmlOptimizer = $this->createMock(HtmlOptimizerInterface::class);
    }

    public function testHooksDoesNothingWhenDisabled(): void
    {
        $minify = new MinifyHtml($this->hookDispatcher, $this->htmlOptimizer, false);

        $this->hookDispatcher->expects($this->never())->method('addAction');

        $minify->hooks();
    }

    public function testHooksRegistersTemplateRedirectWhenEnabled(): void
    {
        $minify = new MinifyHtml($this->hookDispatcher, $this->htmlOptimizer, true);

        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('template_redirect', [$minify, 'startBuffering']);

        $minify->hooks();
    }

    public function testMinifyOutputReturnsEmptyStringUnchanged(): void
    {
        $minify = new MinifyHtml($this->hookDispatcher, $this->htmlOptimizer);

        $this->htmlOptimizer->expects($this->never())->method('optimize');

        $this->assertSame('', $minify->minifyOutput(''));
    }

    public function testMinifyOutputDelegatesToHtmlOptimizer(): void
    {
        $minify = new MinifyHtml($this->hookDispatcher, $this->htmlOptimizer);

        $html = '<html>  <body>   Hello   </body>  </html>';
        $optimized = '<html><body>Hello</body></html>';

        $this->htmlOptimizer->expects($this->once())
            ->method('optimize')
            ->with($html)
            ->willReturn($optimized);

        $this->assertSame($optimized, $minify->minifyOutput($html));
    }
}
