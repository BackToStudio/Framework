<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\RestApi;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Security\FileIntegrityMonitor;
use BackTo\Framework\Security\MalwareScanner;
use WP_REST_Response;

/**
 * REST endpoint to trigger and retrieve security scan results.
 *
 * GET /backto/v1/security/scan — returns file integrity + malware scan results
 */
final class SecurityScanRoute implements RestRouteInterface
{
    private readonly FileIntegrityMonitor $integrityMonitor;
    private readonly MalwareScanner $malwareScanner;

    public function __construct(
        FileIntegrityMonitor $integrityMonitor,
        MalwareScanner $malwareScanner,
    ) {
        $this->integrityMonitor = $integrityMonitor;
        $this->malwareScanner = $malwareScanner;
    }

    public function getNamespace(): string
    {
        return 'backto/v1';
    }

    public function getRoute(): string
    {
        return '/security/scan';
    }

    
    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(mixed $request): mixed
    {
        $integrityResult = $this->integrityMonitor->check();
        $malwareResult = $this->malwareScanner->scan();

        $integrityClean = $integrityResult['modified'] === []
            && $integrityResult['missing'] === []
            && $integrityResult['added'] === [];

        return new WP_REST_Response([
            'file_integrity' => [
                'status' => $integrityClean ? 'clean' : 'alert',
                'modified' => $integrityResult['modified'],
                'missing' => $integrityResult['missing'],
                'added' => $integrityResult['added'],
            ],
            'malware_scan' => [
                'status' => $malwareResult['suspicious_files'] === [] ? 'clean' : 'alert',
                'suspicious_files' => $malwareResult['suspicious_files'],
                'scanned_count' => $malwareResult['scanned_count'],
            ],
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn (): bool => current_user_can('manage_options');
    }
}
