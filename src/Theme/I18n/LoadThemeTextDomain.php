<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\I18n;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

use function load_theme_textdomain;

final class LoadThemeTextDomain implements Hooks
{

    /**
     * @var string
     */
    protected $themeDirectory;

    /**
     * @var string
     */
    protected $textDomain;

    /**
     * @var HookDispatcherInterface
     */
    private $hookDispatcher;

    /**
     * Params are auto-injected by Dependency Injection.
     *
     * @param string $themeDirectory
     * @param string $themeTextDomain
     */
    public function __construct(string $themeDirectory, string $themeTextDomain, HookDispatcherInterface $hookDispatcher)
    {
        $this->themeDirectory = $themeDirectory;
        $this->textDomain = $themeTextDomain;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('after_setup_theme', [$this, 'loadTranslations']);
    }

    /**
     * Load plugin translations.
     */
    public function loadTranslations(): void
    {
        load_theme_textdomain(
            $this->textDomain,
            $this->themeDirectory . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
