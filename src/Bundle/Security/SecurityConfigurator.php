<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

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
        if ($length < 1) {
            throw new \InvalidArgumentException('Password minimum length must be at least 1.');
        }

        $this->overrides['security.password_min_length'] = $length;

        return $this;
    }

    public function maxConcurrentSessions(int $max): self
    {
        if ($max < 1) {
            throw new \InvalidArgumentException('Max concurrent sessions must be at least 1.');
        }

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
        if ($issuer === '') {
            throw new \InvalidArgumentException('Two-factor issuer cannot be empty.');
        }

        $this->overrides['security.two_factor_issuer'] = $issuer;

        return $this;
    }

    public function autoUpdateMajorCore(bool $enabled): self
    {
        $this->overrides['security.auto_update_major_core'] = $enabled;

        return $this;
    }

    public function autoUpdateMinorCore(bool $enabled): self
    {
        $this->overrides['security.auto_update_minor_core'] = $enabled;

        return $this;
    }

    public function autoUpdatePlugins(bool $enabled): self
    {
        $this->overrides['security.auto_update_plugins'] = $enabled;

        return $this;
    }

    public function autoUpdateThemes(bool $enabled): self
    {
        $this->overrides['security.auto_update_themes'] = $enabled;

        return $this;
    }

    public function autoUpdateTranslations(bool $enabled): self
    {
        $this->overrides['security.auto_update_translations'] = $enabled;

        return $this;
    }

    /**
     * @param string[] $basenames Plugin basenames (e.g. 'akismet/akismet.php')
     */
    public function autoUpdateAllowedPlugins(array $basenames): self
    {
        $this->overrides['security.auto_update_allowed_plugins'] = $basenames;

        return $this;
    }

    /**
     * @param string[] $slugs Theme directory names
     */
    public function autoUpdateAllowedThemes(array $slugs): self
    {
        $this->overrides['security.auto_update_allowed_themes'] = $slugs;

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
