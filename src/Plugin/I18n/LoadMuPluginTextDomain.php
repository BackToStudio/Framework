<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\I18n;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

class LoadMuPluginTextDomain implements Hooks
{

    /**
     * @var string
     */
    protected $pluginDirectory;

    /**
     * @var string
     */
    protected $pluginTextDomain;

    /**
     * @var HookDispatcherInterface
     */
    private $hookDispatcher;

    /**
     * Params are auto-injected by Dependency Injection.
     *
     * @param string $pluginDirectory
     * @param string $pluginTextDomain
     */
    public function __construct(string $pluginDirectory, string $pluginTextDomain, HookDispatcherInterface $hookDispatcher)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->pluginTextDomain = $pluginTextDomain;
        $this->hookDispatcher = $hookDispatcher;
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
        \load_muplugin_textdomain(
            $this->pluginTextDomain,
            false,
            basename($this->pluginDirectory) . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
