# Load theme translations manually

`LoadThemeTextDomain` is active automatically. It hooks into `after_setup_theme` and calls `load_theme_textdomain()` with the path `{themeDirectory}/languages`.

If you need to change the translation path, override the service in your configuration and pass a different directory.
