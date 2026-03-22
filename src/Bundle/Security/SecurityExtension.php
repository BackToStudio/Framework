<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;
use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\FileIntegrityRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\InputSanitizerInterface;
use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;
use BackTo\Framework\Bundle\Security\Contracts\LoginLocationRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\AccountLoginThrottleInterface;
use BackTo\Framework\Bundle\Security\Contracts\IpLoginThrottleInterface;
use BackTo\Framework\Bundle\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Bundle\Security\Contracts\NonceManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\OutputEscaperInterface;
use BackTo\Framework\Bundle\Security\Contracts\RateLimiterRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityNotifierInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Contracts\SubresourceIntegrityInterface;
use BackTo\Framework\Bundle\Security\DependencyInjection\Compiler\ConfigureAutoUpdatePolicyPass;
use BackTo\Framework\Bundle\Security\DependencyInjection\Compiler\RegisterSecurityRulePass;
use BackTo\Framework\Bundle\Security\Headers\ContentSecurityPolicyManager;
use BackTo\Framework\Bundle\Security\Headers\CorsManager;
use BackTo\Framework\Bundle\Security\Headers\SubresourceIntegrity;
use BackTo\Framework\Bundle\Security\Audit\SecurityNotifier;
use BackTo\Framework\Bundle\Security\Network\IPAccessControl;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressMailer;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressAuditLogRepository;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressFileIntegrityRepository;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressInputSanitizer;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressLoginLocationRepository;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressLoginThrottle;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressNonceManager;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressOutputEscaper;
use BackTo\Framework\Bundle\Security\Infrastructure\WordPressRateLimiterRepository;
use BackTo\Framework\Bundle\Security\TwoFactor\BackupCodeManager;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorBackupCodeInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorSecretInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorStateInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Infrastructure\WordPressTwoFactorRepository;
use BackTo\Framework\Bundle\Security\TwoFactor\TotpProvider;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class SecurityExtension extends AbstractExtension
{
    /**
     * @return array<int, array{dir: string, namespace: string, exclude: string}>
     */
    public function getBundles(): array
    {
        return [
            // Root-level services (Configuration, Configurator, SecurityRuleRegistry)
            [
                'dir' => __DIR__,
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\',
                'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure,HealthCheck,TwoFactor,Auth,Headers,Audit,Network,Hardening,AuditLog,RestApi}',
            ],
            // Auth: login hardening, sessions, password policy, anomaly detection
            [
                'dir' => __DIR__ . '/Auth',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\Auth\\',
                'exclude' => '{Tests}',
            ],
            // Headers: CSP, CORS, SRI, HTTP security headers
            [
                'dir' => __DIR__ . '/Headers',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\Headers\\',
                'exclude' => '{Tests}',
            ],
            // Audit: audit logging, file integrity, notifications, malware scanning
            [
                'dir' => __DIR__ . '/Audit',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\Audit\\',
                'exclude' => '{Tests}',
            ],
            // Network: IP access control, rate limiting, client IP resolution
            [
                'dir' => __DIR__ . '/Network',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\Network\\',
                'exclude' => '{Tests}',
            ],
            // Hardening: general hardening rules
            [
                'dir' => __DIR__ . '/Hardening',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\Hardening\\',
                'exclude' => '{Tests}',
            ],
            // TwoFactor: TOTP, backup codes, 2FA setup
            [
                'dir' => __DIR__ . '/TwoFactor',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\TwoFactor\\',
                'exclude' => '{Contracts,Infrastructure,Tests}',
            ],
            // AuditLog: renderer
            [
                'dir' => __DIR__ . '/AuditLog',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\AuditLog\\',
                'exclude' => '{Tests}',
            ],
            // RestApi: security routes
            [
                'dir' => __DIR__ . '/RestApi',
                'namespace' => 'BackTo\\Framework\\Bundle\\Security\\RestApi\\',
                'exclude' => '{Tests}',
            ],
        ];
    }

    public function getBundle(): ?array
    {
        return null;
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(SecurityRuleInterface::class)
            ->addTag('wordpress.security_rule');

        $containerBuilder->addCompilerPass(new RegisterSecurityRulePass());
        $containerBuilder->addCompilerPass(new ConfigureAutoUpdatePolicyPass());

        $this->registerPortBindings($containerBuilder);
    }

    public function getDefaultConfiguration(): array
    {
        return SecurityConfiguration::getDefaults();
    }

    private function registerPortBindings(ContainerBuilder $containerBuilder): void
    {
        // Input/Output
        $containerBuilder->register(NonceManagerInterface::class, WordPressNonceManager::class);
        $containerBuilder->setAlias(WordPressNonceManager::class, NonceManagerInterface::class);

        $containerBuilder->register(InputSanitizerInterface::class, WordPressInputSanitizer::class);
        $containerBuilder->setAlias(WordPressInputSanitizer::class, InputSanitizerInterface::class);

        $containerBuilder->register(OutputEscaperInterface::class, WordPressOutputEscaper::class);
        $containerBuilder->setAlias(WordPressOutputEscaper::class, OutputEscaperInterface::class);

        // Auth
        $containerBuilder->register(LoginThrottleInterface::class, WordPressLoginThrottle::class);
        $containerBuilder->setAlias(WordPressLoginThrottle::class, LoginThrottleInterface::class);
        $containerBuilder->setAlias(IpLoginThrottleInterface::class, LoginThrottleInterface::class);
        $containerBuilder->setAlias(AccountLoginThrottleInterface::class, LoginThrottleInterface::class);

        $containerBuilder->register(LoginLocationRepositoryInterface::class, WordPressLoginLocationRepository::class);
        $containerBuilder->setAlias(WordPressLoginLocationRepository::class, LoginLocationRepositoryInterface::class);

        // Headers
        $containerBuilder->register(ContentSecurityPolicyInterface::class, ContentSecurityPolicyManager::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(ContentSecurityPolicyManager::class, ContentSecurityPolicyInterface::class);

        $containerBuilder->register(CorsManagerInterface::class, CorsManager::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(CorsManager::class, CorsManagerInterface::class);

        $containerBuilder->register(SubresourceIntegrityInterface::class, SubresourceIntegrity::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(SubresourceIntegrity::class, SubresourceIntegrityInterface::class);

        // Audit
        $containerBuilder->register(AuditLogRepositoryInterface::class, WordPressAuditLogRepository::class);
        $containerBuilder->setAlias(WordPressAuditLogRepository::class, AuditLogRepositoryInterface::class);

        $containerBuilder->register(FileIntegrityRepositoryInterface::class, WordPressFileIntegrityRepository::class);
        $containerBuilder->setAlias(WordPressFileIntegrityRepository::class, FileIntegrityRepositoryInterface::class);

        $containerBuilder->register(MailerInterface::class, WordPressMailer::class);
        $containerBuilder->setAlias(WordPressMailer::class, MailerInterface::class);

        $containerBuilder->register(SecurityNotifierInterface::class, SecurityNotifier::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(SecurityNotifier::class, SecurityNotifierInterface::class);

        // Network
        $containerBuilder->register(IPAccessControlInterface::class, IPAccessControl::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(IPAccessControl::class, IPAccessControlInterface::class);

        $containerBuilder->register(RateLimiterRepositoryInterface::class, WordPressRateLimiterRepository::class);
        $containerBuilder->setAlias(WordPressRateLimiterRepository::class, RateLimiterRepositoryInterface::class);

        // TwoFactor
        $containerBuilder->register(TotpProviderInterface::class, TotpProvider::class);
        $containerBuilder->setAlias(TotpProvider::class, TotpProviderInterface::class);

        $containerBuilder->register(TwoFactorRepositoryInterface::class, WordPressTwoFactorRepository::class);
        $containerBuilder->setAlias(WordPressTwoFactorRepository::class, TwoFactorRepositoryInterface::class);
        $containerBuilder->setAlias(TwoFactorStateInterface::class, TwoFactorRepositoryInterface::class);
        $containerBuilder->setAlias(TwoFactorSecretInterface::class, TwoFactorRepositoryInterface::class);
        $containerBuilder->setAlias(TwoFactorBackupCodeInterface::class, TwoFactorRepositoryInterface::class);

        $containerBuilder->register(BackupCodeManagerInterface::class, BackupCodeManager::class);
        $containerBuilder->setAlias(BackupCodeManager::class, BackupCodeManagerInterface::class);
    }
}
