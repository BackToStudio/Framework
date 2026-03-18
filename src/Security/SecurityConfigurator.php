<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Security module parameters.
 *
 * Used in config/security.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (SecurityConfigurator $security): void {
 *         $security
 *             ->passwordMinLength(16)
 *             ->twoFactorEnabled(true)
 *             ->twoFactorIssuer('MonApp');
 *     };
 */
final class SecurityConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function headersEnabled(bool $enabled): self
    {
        $this->overrides['security.headers_enabled'] = $enabled;

        return $this;
    }

    public function xmlrpcDisabled(bool $disabled): self
    {
        $this->overrides['security.xmlrpc_disabled'] = $disabled;

        return $this;
    }

    public function hideVersion(bool $hide): self
    {
        $this->overrides['security.hide_version'] = $hide;

        return $this;
    }

    public function cspReportOnly(bool $reportOnly): self
    {
        $this->overrides['security.csp_report_only'] = $reportOnly;

        return $this;
    }

    public function passwordMinLength(int $length): self
    {
        $this->overrides['security.password_min_length'] = $length;

        return $this;
    }

    public function maxConcurrentSessions(int $max): self
    {
        $this->overrides['security.max_concurrent_sessions'] = $max;

        return $this;
    }

    public function restApiRequireAuth(bool $require): self
    {
        $this->overrides['security.rest_api_require_auth'] = $require;

        return $this;
    }

    public function disableFileEditor(bool $disable): self
    {
        $this->overrides['security.disable_file_editor'] = $disable;

        return $this;
    }

    public function twoFactorEnabled(bool $enabled): self
    {
        $this->overrides['security.two_factor_enabled'] = $enabled;

        return $this;
    }

    public function twoFactorIssuer(string $issuer): self
    {
        $this->overrides['security.two_factor_issuer'] = $issuer;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
