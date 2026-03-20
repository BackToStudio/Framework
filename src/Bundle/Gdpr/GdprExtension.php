<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Bundle\Gdpr\DependencyInjection\Compiler\RegisterConsentCategoryPass;
use BackTo\Framework\Bundle\Gdpr\DependencyInjection\Compiler\RegisterTrackingScriptPass;
use BackTo\Framework\Bundle\Gdpr\Infrastructure\CookieConsentStorage;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class GdprExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Gdpr\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure,Preset}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(ConsentCategoryInterface::class)
            ->addTag('wordpress.consent_category');

        $containerBuilder->registerForAutoconfiguration(TrackingScriptInterface::class)
            ->addTag('wordpress.tracking_script');

        $containerBuilder->addCompilerPass(new RegisterConsentCategoryPass());
        $containerBuilder->addCompilerPass(new RegisterTrackingScriptPass());

        $containerBuilder->register(ConsentStorageInterface::class, CookieConsentStorage::class);
        $containerBuilder->setAlias(CookieConsentStorage::class, ConsentStorageInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
