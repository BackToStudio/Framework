<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventSubscriberInterface;
use BackTo\Framework\EventDispatcher\DependencyInjection\Compiler\RegisterEventSubscriberPass;
use BackTo\Framework\EventDispatcher\Infrastructure\WordPressEventBridge;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

final class EventDispatcherExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\EventDispatcher\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        // Autoconfigure: any service implementing EventSubscriberInterface gets tagged.
        $containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('backto.event_subscriber');

        // Compiler pass: collect tagged subscribers into the dispatcher.
        $containerBuilder->addCompilerPass(new RegisterEventSubscriberPass());

        // Core EventDispatcher (in-memory).
        $containerBuilder->register(EventDispatcher::class, EventDispatcher::class);

        // WordPress bridge: decorates the dispatcher to also fire WP hooks.
        $containerBuilder->register(WordPressEventBridge::class, WordPressEventBridge::class)
            ->setArguments([
                new Reference(EventDispatcher::class),
                new Reference(HookDispatcherInterface::class),
            ]);

        // Port binding: EventDispatcherInterface → WordPressEventBridge (decorated).
        $containerBuilder->setAlias(EventDispatcherInterface::class, WordPressEventBridge::class);
    }
}
