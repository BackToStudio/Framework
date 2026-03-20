<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

final class DisableXmlRpc implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'disable_xmlrpc';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('xmlrpc_enabled', '__return_false');
        $this->hookDispatcher->addFilter('wp_headers', [$this, 'removePingbackHeader']);
        $this->hookDispatcher->addAction('wp', [$this, 'removePingbackLink']);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public function removePingbackHeader(array $headers): array
    {
        unset($headers['X-Pingback']);

        return $headers;
    }

    public function removePingbackLink(): void
    {
        $this->hookDispatcher->removeAction('wp_head', 'rsd_link');
        $this->hookDispatcher->removeAction('wp_head', 'wlwmanifest_link');
    }
}
