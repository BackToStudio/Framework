<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\RestApi;

use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Bundle\Security\Audit\FileIntegrityMonitor;
use BackTo\Framework\Bundle\Security\Audit\MalwareScanner;

/**
 * REST endpoint to trigger and retrieve security scan results.
 *
 * GET /backto/v1/security/scan — returns file integrity + malware scan results
 */
final class SecurityScanRoute implements RestRouteInterface
{
    private readonly FileIntegrityMonitor $integrityMonitor;
    private readonly MalwareScanner $malwareScanner;
    private readonly CapabilityManagerInterface $capabilityManager;

    public function __construct(
        FileIntegrityMonitor $integrityMonitor,
        MalwareScanner $malwareScanner,
        CapabilityManagerInterface $capabilityManager,
    ) {
        $this->integrityMonitor = $integrityMonitor;
        $this->malwareScanner = $malwareScanner;
        $this->capabilityManager = $capabilityManager;
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

    public function handle(RestRequest $request): RestResponse
    {
        $integrityResult = $this->integrityMonitor->check();
        $malwareResult = $this->malwareScanner->scan();

        $integrityClean = $integrityResult['modified'] === []
            && $integrityResult['missing'] === []
            && $integrityResult['added'] === [];

        return new RestResponse([
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
        return fn (): bool => $this->capabilityManager->currentUserCan('manage_options');
    }
}
