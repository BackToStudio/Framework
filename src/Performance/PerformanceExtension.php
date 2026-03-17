<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Infrastructure\WordPressDatabaseOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressPageCache;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class PerformanceExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Performance\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $this->registerPortBindings($containerBuilder);
    }

    public function getDefaultConfiguration(): array
    {
        return PerformanceConfiguration::getDefaults();
    }

    private function registerPortBindings(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HtmlOptimizerInterface::class, WordPressHtmlOptimizer::class);
        $containerBuilder->setAlias(WordPressHtmlOptimizer::class, HtmlOptimizerInterface::class);

        $containerBuilder->register(DatabaseOptimizerInterface::class, WordPressDatabaseOptimizer::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressDatabaseOptimizer::class, DatabaseOptimizerInterface::class);

        $containerBuilder->register(PageCacheInterface::class, WordPressPageCache::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressPageCache::class, PageCacheInterface::class);
    }
}
