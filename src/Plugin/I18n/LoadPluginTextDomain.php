<?php

namespace BackTo\Framework\Plugin\I18n;

use BackTo\Framework\Contracts\Hooks;

class LoadPluginTextDomain implements Hooks
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
     * Params are auto-injected by Dependency Injection.
     *
     * @param string $pluginDirectory
     * @param string $pluginTextDomain
     */
    public function __construct(string $pluginDirectory, string $pluginTextDomain)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->pluginTextDomain = $pluginTextDomain;
    }

    public function hooks()
    {
        \add_action('init', [$this, 'loadTranslations']);
    }

    /**
     * Load plugin translations.
     */
    public function loadTranslations()
    {
        \load_plugin_textdomain(
            $this->pluginTextDomain,
            false,
            basename($this->pluginDirectory) . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
