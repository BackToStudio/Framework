<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\CommentSpamProtection;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use PHPUnit\Framework\TestCase;

class TestableCommentSpamProtection extends CommentSpamProtection
{
    private bool $honeypotFilled = false;
    private string $referer = 'https://example.com/post';
    private string $siteHost = 'example.com';
    public bool $commentDenied = false;
    public string $denyMessage = '';

    public function setHoneypotFilled(bool $filled): void
    {
        $this->honeypotFilled = $filled;
    }

    public function setReferer(string $referer): void
    {
        $this->referer = $referer;
    }

    public function setSiteHost(string $host): void
    {
        $this->siteHost = $host;
    }

    protected function isHoneypotFilled(): bool
    {
        return $this->honeypotFilled;
    }

    protected function isValidReferer(): bool
    {
        if ($this->referer === '') {
            return false;
        }

        $refererHost = (string) parse_url($this->referer, PHP_URL_HOST);

        return $refererHost === $this->siteHost;
    }

    protected function getReferer(): string
    {
        return $this->referer;
    }

    protected function getSiteHost(): string
    {
        return $this->siteHost;
    }

    protected function getClientIp(): string
    {
        return '1.2.3.4';
    }

    protected function denyComment(string $message): void
    {
        $this->commentDenied = true;
        $this->denyMessage = $message;
    }
}

class CommentSpamProtectionTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private RequestContextInterface $requestContext;
    private ClientIpResolverInterface $ipResolver;
    private TestableCommentSpamProtection $protection;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->requestContext->method('getRemoteAddr')->willReturn('127.0.0.1');
        $this->requestContext->method('server')->willReturn('');
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->requestContext->method('getUserAgent')->willReturn('');
        $this->requestContext->method('post')->willReturn('');
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $this->protection = new TestableCommentSpamProtection($this->dispatcher, $this->logger, $this->requestContext, $this->ipResolver, $responseEmitter);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->protection);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->protection);
    }

    public function testGetName(): void
    {
        $this->assertSame('comment_spam_protection', $this->protection->getName());
    }

    public function testHooksRegistersActionAndFilter(): void
    {
        $this->dispatcher->expects($this->once())->method('addAction')
            ->with('comment_form', $this->anything());
        $this->dispatcher->expects($this->once())->method('addFilter')
            ->with('preprocess_comment', $this->anything());

        $this->protection->hooks();
    }

    public function testCountLinksHttpUrls(): void
    {
        $content = 'Check http://spam.com and https://evil.com';
        $this->assertSame(2, $this->protection->countLinks($content));
    }

    public function testCountLinksBbcodeUrls(): void
    {
        $content = '[url=http://spam.com]Click[/url]';
        $this->assertSame(2, $this->protection->countLinks($content));
    }

    public function testHasExcessiveLinksDefault(): void
    {
        $content = 'http://a.com http://b.com http://c.com';
        $this->assertTrue($this->protection->hasExcessiveLinks($content));
    }

    public function testHasExcessiveLinksWithinLimit(): void
    {
        $content = 'http://a.com http://b.com';
        $this->assertFalse($this->protection->hasExcessiveLinks($content));
    }

    public function testHasExcessiveLinksCustomLimit(): void
    {
        $this->protection->setMaxLinksAllowed(0);
        $content = 'http://a.com';
        $this->assertTrue($this->protection->hasExcessiveLinks($content));
    }

    public function testDetectSpamPatternsScript(): void
    {
        $patterns = $this->protection->detectSpamPatterns('<script>alert("xss")</script>');
        $this->assertNotEmpty($patterns);
    }

    public function testDetectSpamPatternsIframe(): void
    {
        $patterns = $this->protection->detectSpamPatterns('<iframe src="http://evil.com"></iframe>');
        $this->assertNotEmpty($patterns);
    }

    public function testDetectSpamPatternsOnClick(): void
    {
        $patterns = $this->protection->detectSpamPatterns('<div onclick="alert(1)">');
        $this->assertNotEmpty($patterns);
    }

    public function testDetectSpamPatternsBbcodeUrl(): void
    {
        $patterns = $this->protection->detectSpamPatterns('[url=http://spam.com]Buy now[/url]');
        $this->assertNotEmpty($patterns);
    }

    public function testDetectSpamPatternsHtmlLink(): void
    {
        $patterns = $this->protection->detectSpamPatterns('<a href="http://spam.com">Click</a>');
        $this->assertNotEmpty($patterns);
    }

    public function testDetectSpamPatternsCleanContent(): void
    {
        $patterns = $this->protection->detectSpamPatterns('This is a great article! Thanks for sharing.');
        $this->assertSame([], $patterns);
    }

    public function testValidateCommentBlocksHoneypot(): void
    {
        $this->protection->setHoneypotFilled(true);
        $this->logger->expects($this->once())->method('warning');

        $this->protection->validateComment(['comment_content' => 'spam']);

        $this->assertTrue($this->protection->commentDenied);
    }

    public function testValidateCommentBlocksInvalidReferer(): void
    {
        $this->protection->setReferer('https://evil.com/fake');

        $this->protection->validateComment(['comment_content' => 'hi']);

        $this->assertTrue($this->protection->commentDenied);
    }

    public function testValidateCommentBlocksEmptyReferer(): void
    {
        $this->protection->setReferer('');

        $this->protection->validateComment(['comment_content' => 'hi']);

        $this->assertTrue($this->protection->commentDenied);
    }

    public function testValidateCommentBlocksExcessiveLinks(): void
    {
        $this->protection->validateComment([
            'comment_content' => 'http://a.com http://b.com http://c.com',
        ]);

        $this->assertTrue($this->protection->commentDenied);
    }

    public function testValidateCommentBlocksSpamPatterns(): void
    {
        $this->protection->validateComment([
            'comment_content' => '<script>alert("xss")</script>',
        ]);

        $this->assertTrue($this->protection->commentDenied);
    }

    public function testValidateCommentAllowsCleanComment(): void
    {
        $data = ['comment_content' => 'Great article, thanks!'];
        $result = $this->protection->validateComment($data);

        $this->assertFalse($this->protection->commentDenied);
        $this->assertSame($data, $result);
    }

    public function testRenderHoneypotProducesHiddenField(): void
    {
        ob_start();
        $this->protection->renderHoneypot();
        $output = ob_get_clean();

        $this->assertStringContainsString('aria-hidden="true"', $output);
        $this->assertStringContainsString('name="website_url_confirm"', $output);
        $this->assertStringContainsString('tabindex="-1"', $output);
    }
}
