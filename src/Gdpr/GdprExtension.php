<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\DependencyInjection\Compiler\RegisterConsentCategoryPass;
use BackTo\Framework\Gdpr\DependencyInjection\Compiler\RegisterTrackingScriptPass;
use BackTo\Framework\Gdpr\Infrastructure\CookieConsentStorage;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class GdprExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Gdpr\\',
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
