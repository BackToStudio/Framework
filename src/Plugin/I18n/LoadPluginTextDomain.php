<?php

namespace Fantassin\Core\WordPress\Plugin\I18n;

use Fantassin\Core\WordPress\Contracts\Hooks;

class LoadPluginTextDomain implements Hooks
{

    /**
     * @var string
     */
    protected $pluginDirectory;

    /**
     * @var string
     */
    protected $pluginName;

    /**
     * Params are auto-injected by Dependency Injection.
     *
     * @param string $pluginDirectory
     * @param string $pluginName
     */
    public function __construct(string $pluginDirectory, string $pluginName)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->pluginName = $pluginName;
    }

    public function hooks()
    {
        add_action('init', [$this, 'load_translations']);
    }

    /**
     * Load plugin translations.
     */
    public function load_translations()
    {
        load_plugin_textdomain(
            $this->pluginName,
            false,
            basename($this->pluginDirectory) . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
