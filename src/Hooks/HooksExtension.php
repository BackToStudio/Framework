<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class HooksExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Hooks\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(HookInterface::class)
            ->addTag('wordpress.hook');

        $containerBuilder->addCompilerPass(new RegisterHookPass());

        $containerBuilder->register(HookDispatcherInterface::class, WordPressHookDispatcher::class);
        $containerBuilder->setAlias(WordPressHookDispatcher::class, HookDispatcherInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
