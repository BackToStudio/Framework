<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\Hooks\Html\MinifyHtml;
use BackTo\Framework\Bundle\Performance\Hooks\Html\RemoveUnusedCss;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressDatabaseOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressPageCache;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class PerformanceExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return null;
    }

    /**
     * @return array<int, array{dir: string, namespace: string, exclude: string}>
     */
    public function getBundles(): array
    {
        return [
            // Root-level services (Configurator, Configuration, support classes)
            [
                'dir' => __DIR__,
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\',
                'exclude' => '{Tests,Contracts,Infrastructure,Hooks,Css}',
            ],
            // Cache: page cache, preloading, invalidation
            [
                'dir' => __DIR__ . '/Hooks/Cache',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Hooks\\Cache\\',
                'exclude' => '{Tests}',
            ],
            // Assets: JS deferral, resource hints, image optimization
            [
                'dir' => __DIR__ . '/Hooks/Assets',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Hooks\\Assets\\',
                'exclude' => '{Tests}',
            ],
            // Html: minification, unused CSS removal
            [
                'dir' => __DIR__ . '/Hooks/Html',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Hooks\\Html\\',
                'exclude' => '{Tests}',
            ],
            // Cleanup: heartbeat, revisions, dashboard, WooCommerce, head, XML-RPC, embeds, emojis
            [
                'dir' => __DIR__ . '/Hooks/Cleanup',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Hooks\\Cleanup\\',
                'exclude' => '{Tests}',
            ],
            // Server: htaccess optimization
            [
                'dir' => __DIR__ . '/Hooks/Server',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Hooks\\Server\\',
                'exclude' => '{Tests}',
            ],
            // CSS utilities (CssRuleFilter, SelectorMatcher, HtmlSelectorExtractor)
            [
                'dir' => __DIR__ . '/Css',
                'namespace' => 'BackTo\\Framework\\Bundle\\Performance\\Css\\',
                'exclude' => '{Tests}',
            ],
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
