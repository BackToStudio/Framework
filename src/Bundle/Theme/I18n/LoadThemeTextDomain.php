<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\I18n;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Plugin\Contracts\TextDomainLoaderInterface;

final class LoadThemeTextDomain implements Hooks
{
    private readonly string $themeDirectory;
    private readonly string $textDomain;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly TextDomainLoaderInterface $textDomainLoader;

    /**
     * Params are auto-injected by Dependency Injection.
     */
    public function __construct(
        string $themeDirectory,
        string $themeTextDomain,
        HookDispatcherInterface $hookDispatcher,
        TextDomainLoaderInterface $textDomainLoader,
    ) {
        $this->themeDirectory = $themeDirectory;
        $this->textDomain = $themeTextDomain;
        $this->hookDispatcher = $hookDispatcher;
        $this->textDomainLoader = $textDomainLoader;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('after_setup_theme', [$this, 'loadTranslations']);
    }

    /**
     * Load theme translations.
     */
    public function loadTranslations(): void
    {
        $this->textDomainLoader->loadThemeTextDomain(
            $this->textDomain,
            $this->themeDirectory . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
