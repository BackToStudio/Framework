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
    use ClientIpTrait;

    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoggerInterface $logger;

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

    
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    
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
     * Check if an IP matches a CIDR range. Supports both IPv4 and IPv6.
     */
    public function matchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);

        if (! ctype_digit($bits)) {
            $this->logger->warning('Invalid CIDR notation: non-numeric prefix length', [
                'cidr' => $cidr,
            ]);

            return false;
        }

        $bits = (int) $bits;

        if (str_contains($ip, ':') || str_contains($subnet, ':')) {
            return $this->matchesIpv6Cidr($ip, $subnet, $bits);
        }

        return $this->matchesIpv4Cidr($ip, $subnet, $bits);
    }

    private function matchesIpv4Cidr(string $ip, string $subnet, int $bits): bool
    {
        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function matchesIpv6Cidr(string $ip, string $subnet, int $bits): bool
    {
        if ($bits < 0 || $bits > 128) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // Compare bit-by-bit up to the prefix length
        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        // Compare full bytes
        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        // Compare remaining bits in the next byte
        if ($remainingBits > 0 && $fullBytes < strlen($ipBin)) {
            $mask = 0xFF << (8 - $remainingBits) & 0xFF;

            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($subnetBin[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
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

    
    private function matchesAny(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            if ($this->matchesCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
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
