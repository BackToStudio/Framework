<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

final class HideWordPressVersion implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'hide_wordpress_version';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('the_generator', [$this, 'removeGenerator']);
        $this->hookDispatcher->addFilter('style_loader_src', [$this, 'removeVersionFromUrl']);
        $this->hookDispatcher->addFilter('script_loader_src', [$this, 'removeVersionFromUrl']);
        $this->hookDispatcher->addAction('wp_head', [$this, 'removeHeadMeta'], 1);
    }

    public function removeGenerator(): string
    {
        return '';
    }

    public function removeVersionFromUrl(string $src): string
    {
        if (!str_contains($src, 'ver=')) {
            return $src;
        }

        $questionMarkPos = strpos($src, '?');

        if ($questionMarkPos === false) {
            return $src;
        }

        $base = substr($src, 0, $questionMarkPos);
        $query = substr($src, $questionMarkPos + 1);

        parse_str($query, $params);
        unset($params['ver']);

        if ($params === []) {
            return $base;
        }

        return $base . '?' . http_build_query($params);
    }

    public function removeHeadMeta(): void
    {
        $this->hookDispatcher->removeAction('wp_head', 'wp_generator');
    }
}
