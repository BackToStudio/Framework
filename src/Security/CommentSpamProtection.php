<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

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
    use ClientIpTrait;
    use HtmlEscapeTrait;

    private HookDispatcherInterface $hookDispatcher;
    private LoggerInterface $logger;

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
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
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
                'ip' => $this->getClientIp(),
            ]);
            $this->denyComment('Spam detected.');
        }

        if (! $this->isValidReferer()) {
            $this->logger->warning('Comment spam: invalid referer', [
                'ip' => $this->getClientIp(),
                'referer' => $this->getReferer(),
            ]);
            $this->denyComment('Invalid submission origin.');
        }

        if ($this->hasExcessiveLinks($content)) {
            $this->logger->warning('Comment spam: excessive links', [
                'ip' => $this->getClientIp(),
                'link_count' => $this->countLinks($content),
            ]);
            $this->denyComment('Too many links in comment.');
        }

        $spamPatterns = $this->detectSpamPatterns($content);

        if ($spamPatterns !== []) {
            $this->logger->warning('Comment spam: dangerous patterns', [
                'ip' => $this->getClientIp(),
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
        return isset($_POST[$this->honeypotFieldName]) && $_POST[$this->honeypotFieldName] !== '';
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
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    protected function getSiteHost(): string
    {
        return (string) parse_url(\site_url(), PHP_URL_HOST);
    }

    protected function denyComment(string $message): void
    {
        wp_die($message, 'Comment Blocked', ['response' => 403, 'back_link' => true]);
    }

}
