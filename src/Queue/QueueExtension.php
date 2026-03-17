<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\Contracts\QueueDispatcherInterface;
use BackTo\Framework\Queue\Contracts\QueueRepositoryInterface;
use BackTo\Framework\Queue\DependencyInjection\Compiler\RegisterQueuePass;
use BackTo\Framework\Queue\Infrastructure\WordPressQueueRepository;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class QueueExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Queue\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(JobInterface::class)
            ->addTag('wordpress.queue_job');

        $containerBuilder->addCompilerPass(new RegisterQueuePass());

        $containerBuilder->register(QueueRepositoryInterface::class, WordPressQueueRepository::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressQueueRepository::class, QueueRepositoryInterface::class);

        $containerBuilder->register(QueueDispatcherInterface::class, QueueDispatcher::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(QueueDispatcher::class, QueueDispatcherInterface::class)
            ->setPublic(true);
    }

    public function getDefaultConfiguration(): array
    {
        return [
            'framework.queue.batch_size' => 10,
            'framework.queue.rescue_timeout' => 300,
            'framework.queue.cleanup_age' => 86400,
        ];
    }
}
