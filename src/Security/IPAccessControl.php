<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\IPAccessControlInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * IP-based access control for the WordPress admin and sensitive endpoints.
 *
 * Supports both whitelist and blacklist modes:
 * - Whitelist: if any IPs are whitelisted, ONLY those IPs can access protected areas
 * - Blacklist: blocks specific IPs from accessing protected areas
 * - Supports CIDR notation (e.g., 192.168.1.0/24)
 */
class IPAccessControl implements Hooks, SecurityRuleInterface, IPAccessControlInterface
{
    private HookDispatcherInterface $hookDispatcher;
    private LoggerInterface $logger;

    /** @var string[] */
    private array $whitelist = [];

    /** @var string[] */
    private array $blacklist = [];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'ip_access_control';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_init', [$this, 'checkAdminAccess']);
        $this->hookDispatcher->addAction('login_init', [$this, 'checkLoginAccess']);
    }

    public function addToWhitelist(string $ip): self
    {
        if (! in_array($ip, $this->whitelist, true)) {
            $this->whitelist[] = $ip;
        }

        return $this;
    }

    public function addToBlacklist(string $ip): self
    {
        if (! in_array($ip, $this->blacklist, true)) {
            $this->blacklist[] = $ip;
        }

        return $this;
    }

    public function isAllowed(string $ip): bool
    {
        // If whitelist is active, only whitelisted IPs are allowed
        if ($this->whitelist !== []) {
            return $this->matchesAny($ip, $this->whitelist);
        }

        // If no whitelist, allow unless blacklisted
        return ! $this->isBlocked($ip);
    }

    public function isBlocked(string $ip): bool
    {
        return $this->matchesAny($ip, $this->blacklist);
    }

    /**
     * @return string[]
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * @return string[]
     */
    public function getBlacklist(): array
    {
        return $this->blacklist;
    }

    public function checkAdminAccess(): void
    {
        $this->enforceAccess('admin');
    }

    public function checkLoginAccess(): void
    {
        $this->enforceAccess('login');
    }

    /**
     * Check if an IP matches a CIDR range.
     */
    public function matchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function enforceAccess(string $area): void
    {
        if ($this->whitelist === [] && $this->blacklist === []) {
            return;
        }

        $ip = $this->getClientIp();

        if ($this->isAllowed($ip)) {
            return;
        }

        $this->logger->warning('IP access denied', [
            'ip' => $ip,
            'area' => $area,
        ]);

        $this->denyAccess();
    }

    /**
     * @param string[] $list
     */
    private function matchesAny(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            if ($this->matchesCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function denyAccess(): void
    {
        wp_die(
            'Access denied. Your IP address is not authorized to access this area.',
            'Forbidden',
            ['response' => 403]
        );
    }
}
