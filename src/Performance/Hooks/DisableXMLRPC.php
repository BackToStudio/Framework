<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Disable XML-RPC and remove pingback headers.
 *
 * Reduces attack surface and prevents DDoS amplification via pingback.
 */
final class DisableXMLRPC implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('xmlrpc_enabled', '__return_false');
        $this->hookDispatcher->addFilter('wp_headers', [$this, 'removePingbackHeader']);
        $this->hookDispatcher->removeAction('wp_head', 'rsd_link');
    }

    /**
     * Remove X-Pingback header from responses.
     *
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public function removePingbackHeader(array $headers): array
    {
        unset($headers['X-Pingback']);

        return $headers;
    }
}
