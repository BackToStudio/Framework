<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DisableXmlRpc implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;

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
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
    }
}
