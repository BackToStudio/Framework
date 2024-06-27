<?php

namespace BackTo\Framework\Theme\I18n;

use BackTo\Framework\Contracts\Hooks;

class LoadThemeTextDomain implements Hooks
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
     * Params are auto-injected by Dependency Injection.
     *
     * @param string $themeDirectory
     * @param string $themeTextDomain
     */
    public function __construct(string $themeDirectory, string $themeTextDomain)
    {
        $this->themeDirectory = $themeDirectory;
        $this->textDomain = $themeTextDomain;
    }

    public function hooks()
    {
        \add_action('after_setup_theme', [$this, 'loadTranslations']);
    }

    /**
     * Load plugin translations.
     */
    public function loadTranslations()
    {
        \load_theme_textdomain(
            $this->textDomain,
            $this->themeDirectory . DIRECTORY_SEPARATOR . 'languages'
        );
    }
}
