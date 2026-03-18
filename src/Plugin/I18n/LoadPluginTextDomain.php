<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\I18n;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Plugin\Contracts\TextDomainLoaderInterface;

final class LoadPluginTextDomain implements Hooks
{
    private readonly string $pluginDirectory;
    private readonly string $pluginTextDomain;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly TextDomainLoaderInterface $textDomainLoader;

    /**
     * Params are auto-injected by Dependency Injection.
     */
    public function __construct(
        string $pluginDirectory,
        string $pluginTextDomain,
        HookDispatcherInterface $hookDispatcher,
        TextDomainLoaderInterface $textDomainLoader,
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->pluginTextDomain = $pluginTextDomain;
        $this->hookDispatcher = $hookDispatcher;
        $this->textDomainLoader = $textDomainLoader;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'loadTranslations']);
    }

    /**
     * Load plugin translations.
     */
    public function loadTranslations(): void
    {
        $this->textDomainLoader->loadPluginTextDomain(
            $this->pluginTextDomain,
            basename($this->pluginDirectory) . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
