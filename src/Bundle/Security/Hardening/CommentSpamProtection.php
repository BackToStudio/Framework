<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Hardening;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Protects comment forms against spam and injection attacks.
 *
 * Three layers of protection:
 * - Honeypot field: hidden field that bots fill, humans don't
 * - Referer validation: ensures submissions come from the site
 * - Content filtering: blocks excessive links and dangerous HTML patterns
 */
class CommentSpamProtection implements Hooks, SecurityRuleInterface
{
    use HtmlEscapeTrait;

    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoggerInterface $logger;
    private readonly RequestContextInterface $requestContext;
    private readonly ClientIpResolverInterface $ipResolver;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly SiteContextInterface $siteContext;

    private int $maxLinksAllowed = 2;
    private string $honeypotFieldName = 'website_url_confirm';

    /** @var string[] Patterns that indicate spam/injection in comment content */
    private const SPAM_PATTERNS = [
        '/\[url[=\]]/i',
        '/<a\s+href/i',
        '/\bon\w+\s*=/i',
        '/<script/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<form/i',
    ];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
        RequestContextInterface $requestContext,
        ClientIpResolverInterface $ipResolver,
        ResponseEmitterInterface $responseEmitter,
        SiteContextInterface $siteContext,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
        $this->requestContext = $requestContext;
        $this->ipResolver = $ipResolver;
        $this->responseEmitter = $responseEmitter;
        $this->siteContext = $siteContext;
    }

    public function getName(): string
    {
        return 'comment_spam_protection';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('comment_form', [$this, 'renderHoneypot']);
        $this->hookDispatcher->addFilter('preprocess_comment', [$this, 'validateComment']);
    }

    public function setMaxLinksAllowed(int $max): self
    {
        $this->maxLinksAllowed = $max;

        return $this;
    }

    public function setHoneypotFieldName(string $name): self
    {
        $this->honeypotFieldName = $name;

        return $this;
    }

    public function renderHoneypot(): void
    {
        echo '<div style="position:absolute;left:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">';
        echo '<label for="' . $this->escapeAttr($this->honeypotFieldName) . '">Leave empty</label>';
        echo '<input type="text" name="' . $this->escapeAttr($this->honeypotFieldName) . '" value="" tabindex="-1" autocomplete="off" />';
        echo '</div>';
    }

    /**
     * Validate a comment before it is saved.
     *
     * @param array<string, mixed> $commentData
     * @return array<string, mixed>
     */
    public function validateComment(array $commentData): array
    {
        $content = (string) ($commentData['comment_content'] ?? '');

        if ($this->isHoneypotFilled()) {
            $this->logger->warning('Comment spam: honeypot filled', [
                'ip' => $this->ipResolver->getClientIp(),
            ]);
            $this->denyComment('Spam detected.');
        }

        if (! $this->isValidReferer()) {
            $this->logger->warning('Comment spam: invalid referer', [
                'ip' => $this->ipResolver->getClientIp(),
                'referer' => $this->getReferer(),
            ]);
            $this->denyComment('Invalid submission origin.');
        }

        if ($this->hasExcessiveLinks($content)) {
            $this->logger->warning('Comment spam: excessive links', [
                'ip' => $this->ipResolver->getClientIp(),
                'link_count' => $this->countLinks($content),
            ]);
            $this->denyComment('Too many links in comment.');
        }

        $spamPatterns = $this->detectSpamPatterns($content);

        if ($spamPatterns !== []) {
            $this->logger->warning('Comment spam: dangerous patterns', [
                'ip' => $this->ipResolver->getClientIp(),
                'patterns' => $spamPatterns,
            ]);
            $this->denyComment('Comment contains disallowed content.');
        }

        return $commentData;
    }

    /**
     * Check if comment content has more links than allowed.
     */
    public function hasExcessiveLinks(string $content): bool
    {
        return $this->countLinks($content) > $this->maxLinksAllowed;
    }

    /**
     * Count links in content (both HTML and BBCode style).
     */
    public function countLinks(string $content): int
    {
        $httpCount = (int) preg_match_all('/https?:\/\//i', $content);
        $bbcodeCount = (int) preg_match_all('/\[url/i', $content);

        return $httpCount + $bbcodeCount;
    }

    /**
     * Detect spam/injection patterns in content.
     *
     * @return string[]
     */
    public function detectSpamPatterns(string $content): array
    {
        $matches = [];

        foreach (self::SPAM_PATTERNS as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                $matches[] = $pattern;
            }
        }

        return $matches;
    }

    protected function isHoneypotFilled(): bool
    {
        $value = $this->requestContext->post($this->honeypotFieldName);

        return $value !== '';
    }

    protected function isValidReferer(): bool
    {
        $referer = $this->getReferer();

        if ($referer === '') {
            return false;
        }

        $siteHost = $this->getSiteHost();
        $refererHost = (string) parse_url($referer, PHP_URL_HOST);

        return $refererHost === $siteHost;
    }

    protected function getReferer(): string
    {
        return $this->requestContext->server('HTTP_REFERER');
    }

    protected function getSiteHost(): string
    {
        return (string) parse_url($this->siteContext->getSiteUrl(), PHP_URL_HOST);
    }

    protected function denyComment(string $message): void
    {
        $this->responseEmitter->setStatusCode(403);
        $this->responseEmitter->sendHeader('Content-Type: text/html; charset=UTF-8');
        echo $this->escapeHtml($message);
        $this->responseEmitter->terminate();
    }
}
