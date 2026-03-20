<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\EscaperInterface;
use BackTo\Framework\Contracts\NonceManagerInterface;
use BackTo\Framework\Contracts\PluginCheckerInterface;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Contracts\ScriptManagerInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\WordPress\Infrastructure\WordPressContentQuery;
use BackTo\Framework\WordPress\Infrastructure\WordPressEscaper;
use BackTo\Framework\WordPress\Infrastructure\WordPressNonceManager;
use BackTo\Framework\WordPress\Infrastructure\WordPressPluginChecker;
use BackTo\Framework\WordPress\Infrastructure\WordPressQueryContext;
use BackTo\Framework\WordPress\Infrastructure\WordPressScriptManager;
use BackTo\Framework\WordPress\Infrastructure\WordPressSiteContext;
use BackTo\Framework\WordPress\Infrastructure\WordPressUserContext;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers generic WordPress platform adapters.
 *
 * These adapters bridge the framework's port interfaces (Contracts/)
 * to their WordPress implementations. They have no conceptual link
 * to any specific domain — they are foundational WordPress adapters.
 */
final class WordPressExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return null;
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(UserContextInterface::class, WordPressUserContext::class);
        $containerBuilder->setAlias(WordPressUserContext::class, UserContextInterface::class);

        $containerBuilder->register(SiteContextInterface::class, WordPressSiteContext::class);
        $containerBuilder->setAlias(WordPressSiteContext::class, SiteContextInterface::class);

        $containerBuilder->register(QueryContextInterface::class, WordPressQueryContext::class);
        $containerBuilder->setAlias(WordPressQueryContext::class, QueryContextInterface::class);

        $containerBuilder->register(ContentQueryInterface::class, WordPressContentQuery::class);
        $containerBuilder->setAlias(WordPressContentQuery::class, ContentQueryInterface::class);

        $containerBuilder->register(EscaperInterface::class, WordPressEscaper::class);
        $containerBuilder->setAlias(WordPressEscaper::class, EscaperInterface::class);

        $containerBuilder->register(ScriptManagerInterface::class, WordPressScriptManager::class);
        $containerBuilder->setAlias(WordPressScriptManager::class, ScriptManagerInterface::class);

        $containerBuilder->register(NonceManagerInterface::class, WordPressNonceManager::class);
        $containerBuilder->setAlias(WordPressNonceManager::class, NonceManagerInterface::class);

        $containerBuilder->register(PluginCheckerInterface::class, WordPressPluginChecker::class);
        $containerBuilder->setAlias(WordPressPluginChecker::class, PluginCheckerInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
