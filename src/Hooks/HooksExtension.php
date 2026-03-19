<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\EscaperInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\HttpClientInterface;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Contracts\ScriptManagerInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Hooks\Contracts\HookRegistryInterface;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\Hooks\Infrastructure\WordPressContentQuery;
use BackTo\Framework\Hooks\Infrastructure\WordPressEscaper;
use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use BackTo\Framework\Hooks\Infrastructure\WordPressHttpClient;
use BackTo\Framework\Hooks\Infrastructure\WordPressQueryContext;
use BackTo\Framework\Hooks\Infrastructure\WordPressScriptManager;
use BackTo\Framework\Hooks\Infrastructure\WordPressSiteContext;
use BackTo\Framework\Hooks\Infrastructure\WordPressUserContext;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class HooksExtension extends AbstractExtension
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

        $containerBuilder->register(UserContextInterface::class, WordPressUserContext::class);
        $containerBuilder->setAlias(WordPressUserContext::class, UserContextInterface::class);

        $containerBuilder->register(SiteContextInterface::class, WordPressSiteContext::class);
        $containerBuilder->setAlias(WordPressSiteContext::class, SiteContextInterface::class);

        $containerBuilder->register(QueryContextInterface::class, WordPressQueryContext::class);
        $containerBuilder->setAlias(WordPressQueryContext::class, QueryContextInterface::class);

        $containerBuilder->register(ContentQueryInterface::class, WordPressContentQuery::class);
        $containerBuilder->setAlias(WordPressContentQuery::class, ContentQueryInterface::class);

        $containerBuilder->register(HttpClientInterface::class, WordPressHttpClient::class);
        $containerBuilder->setAlias(WordPressHttpClient::class, HttpClientInterface::class);

        $containerBuilder->register(EscaperInterface::class, WordPressEscaper::class);
        $containerBuilder->setAlias(WordPressEscaper::class, EscaperInterface::class);

        $containerBuilder->register(ScriptManagerInterface::class, WordPressScriptManager::class);
        $containerBuilder->setAlias(WordPressScriptManager::class, ScriptManagerInterface::class);

        $containerBuilder->setAlias(HookRegistryInterface::class, HookRegistry::class)
            ->setPublic(true);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
