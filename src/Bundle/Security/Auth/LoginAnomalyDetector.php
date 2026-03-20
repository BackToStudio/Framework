<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Auth;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\LoginLocationRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Detects anomalous login patterns by tracking login locations.
 *
 * Monitors for:
 * - Logins from previously unseen countries
 * - Logins from new IP addresses
 * - Rapid login location changes (impossible travel)
 *
 * Hook priorities:
 * - wp_login (20): After SecurityAuditLogger (10) and LoginHardening (10),
 *   ensures anomaly detection runs after login recording is complete
 */
class LoginAnomalyDetector implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoginLocationRepositoryInterface $repository;
    private readonly LoggerInterface $logger;
    private readonly RequestContextInterface $requestContext;
    private readonly ClientIpResolverInterface $ipResolver;

    /** @var int Max seconds between logins from different countries to flag impossible travel */
    private const IMPOSSIBLE_TRAVEL_THRESHOLD = 3600; // 1 hour

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoginLocationRepositoryInterface $repository,
        LoggerInterface $logger,
        RequestContextInterface $requestContext,
        ClientIpResolverInterface $ipResolver,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->requestContext = $requestContext;
        $this->ipResolver = $ipResolver;
    }

    public function getName(): string
    {
        return 'login_anomaly_detector';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_login', [$this, 'onLogin'], 20, 2);
    }

    public function onLogin(string $username, mixed $user): void
    {
        $userId = $this->getUserId($user);

        if ($userId === null) {
            return;
        }

        $ip = $this->ipResolver->getClientIp();
        $country = $this->getCountryFromIp($ip);
        $userAgent = $this->getUserAgent();

        $locationData = [
            'ip' => $ip,
            'country' => $country,
            'user_agent' => $userAgent,
            'timestamp' => time(),
        ];

        $anomalies = $this->detectAnomalies($userId, $locationData);

        if ($anomalies !== []) {
            $this->logger->warning('Login anomaly detected', [
                'user_id' => $userId,
                'username' => $username,
                'anomalies' => $anomalies,
                'ip' => $ip,
                'country' => $country,
            ]);
        }

        $this->repository->recordLogin($userId, $locationData);
    }

    /**
     * Detect anomalies for a login attempt.
     *
     * @param array<string, mixed> $locationData
     * @return string[]
     */
    public function detectAnomalies(int $userId, array $locationData): array
    {
        $anomalies = [];

        $country = (string) ($locationData['country'] ?? 'unknown');

        // Check for new country
        if ($country !== 'unknown') {
            $knownCountries = $this->repository->getKnownCountries($userId);

            if ($knownCountries !== [] && ! in_array($country, $knownCountries, true)) {
                $anomalies[] = 'new_country:' . $country;
            }
        }

        // Check for impossible travel
        $recentLogins = $this->repository->getLoginHistory($userId, 1);

        if ($recentLogins !== []) {
            $lastLogin = $recentLogins[0];
            $lastCountry = (string) ($lastLogin['country'] ?? 'unknown');
            $lastTimestamp = (int) ($lastLogin['timestamp'] ?? 0);
            $currentTimestamp = (int) ($locationData['timestamp'] ?? time());

            if (
                $lastCountry !== 'unknown'
                && $country !== 'unknown'
                && $lastCountry !== $country
                && ($currentTimestamp - $lastTimestamp) < self::IMPOSSIBLE_TRAVEL_THRESHOLD
            ) {
                $anomalies[] = 'impossible_travel:' . $lastCountry . '->' . $country;
            }
        }

        return $anomalies;
    }

    protected function getUserAgent(): string
    {
        return $this->requestContext->server('HTTP_USER_AGENT');
    }

    protected function getUserId(mixed $user): ?int
    {
        if (is_object($user) && isset($user->ID)) {
            return (int) $user->ID;
        }

        return null;
    }

    /**
     * Resolve country from IP address.
     *
     * Uses CloudFlare/CDN headers when available, falls back to unknown.
     * For full GeoIP support, override this method with a GeoIP service.
     */
    protected function getCountryFromIp(string $ip): string
    {
        $remoteAddr = $this->requestContext->server('REMOTE_ADDR', '0.0.0.0');
        /** @var string[] $trustedProxies */
        $trustedProxies = $this->hookDispatcher->applyFilters('backto_trusted_proxies', []);

        $isTrustedProxy = $trustedProxies !== [] && in_array($remoteAddr, $trustedProxies, true);

        // Only trust CDN/proxy country headers when behind a trusted proxy
        if ($isTrustedProxy) {
            $cfCountry = $this->requestContext->server('HTTP_CF_IPCOUNTRY');

            if ($cfCountry !== '') {
                return strtoupper($cfCountry);
            }

            $xCountryCode = $this->requestContext->server('HTTP_X_COUNTRY_CODE');

            if ($xCountryCode !== '') {
                return strtoupper($xCountryCode);
            }
        }

        return 'unknown';
    }
}
