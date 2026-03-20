<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\Hooks\MinifyHtml;
use BackTo\Framework\Bundle\Performance\Hooks\RemoveUnusedCss;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressDatabaseOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressPageCache;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class PerformanceExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $this->registerPortBindings($containerBuilder);
        $this->configureConditionalHooks($containerBuilder);
    }

    public function getDefaultConfiguration(): array
    {
        return PerformanceConfiguration::getDefaults();
    }

    private function configureConditionalHooks(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->getDefinition(MinifyHtml::class)
            ->setArgument('$enabled', '%performance.minify_html%');

        $containerBuilder->getDefinition(RemoveUnusedCss::class)
            ->setArgument('$enabled', '%performance.remove_unused_css%');
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
