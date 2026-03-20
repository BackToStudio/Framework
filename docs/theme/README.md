# Theme Bundle

Le bundle `BackTo\Framework\Bundle\Theme` fournit le kernel de demarrage pour les themes WordPress construits avec le BackTo Framework. Il inclut un ensemble d'actions de nettoyage du `<head>` HTML et le chargement automatique des traductions du theme.

## Tutorial

### Demarrer un theme avec ThemeKernel

1. **Dans votre `functions.php`**, initialisez le kernel :

```php
<?php

declare(strict_types=1);

use BackTo\Framework\Bundle\Theme\ThemeKernel;

$kernel = new ThemeKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->setProjectDir(get_template_directory());
$kernel->setTextDomain('mon-theme');
$kernel->boot();
```

2. **Ajoutez vos extensions** avant le boot :

```php
$kernel->addExtension(new BlocksExtension());
$kernel->addExtension(new AdminExtension());
$kernel->boot();
```

3. **Creez le dossier `languages/`** a la racine de votre theme pour les fichiers de traduction.

Les actions de nettoyage (`CleanHead`, `RemoveEmojis`, etc.) sont activees automatiquement via la configuration des services du bundle.

## How-to

### Nettoyer le `<head>` WordPress

Le bundle fournit plusieurs actions activees par defaut. Pour les desactiver individuellement, excluez-les de votre configuration de services.

**Actions disponibles :**

- **`CleanHead`** : supprime les flux RSS, les liens RSD, WLW manifest, les liens relationnels.
- **`RemoveEmojis`** : supprime tous les scripts et styles lies aux emojis WordPress (front et admin).
- **`RemoveWordPressVersion`** : masque le numero de version WordPress dans le `<head>` et les flux RSS.
- **`RemoveSvgFilters`** : supprime les filtres SVG globaux injectes par WordPress et Gutenberg au `wp_body_open`.
- **`RemoveNavigationFallback`** : desactive le rendu fallback du bloc Navigation.

### Desactiver une action de nettoyage specifique

Si vous souhaitez conserver les emojis WordPress, excluez `RemoveEmojis` de l'autoloading des services :

```php
$services->load('BackTo\\Framework\\Bundle\\Theme\\Actions\\', 'Actions/*')
    ->exclude('Actions/RemoveEmojis.php');
```

### Charger les traductions du theme

`LoadThemeTextDomain` est active automatiquement. Il se branche sur `after_setup_theme` et appelle `load_theme_textdomain()` avec le chemin `{themeDirectory}/languages`.

## Reference

### Classes

#### `ThemeKernel`

Etend `AbstractKernel`. Point d'entree du theme.

| Methode protegee | Retour | Description |
|---|---|---|
| `getDirectoryParameterName()` | `string` | Retourne `'themeDirectory'` |
| `getTextDomainParameterName()` | `string` | Retourne `'themeTextDomain'` |
| `getKernelConfigDir()` | `string` | Chemin vers `Resources/config` |

#### `LoadThemeTextDomain`

Implemente `Hooks`. Charge les traductions au hook `after_setup_theme`.

| Parametre injecte | Description |
|---|---|
| `$themeDirectory` | Repertoire racine du theme |
| `$themeTextDomain` | Text domain du theme |

### Actions de nettoyage

Toutes implementent `Hooks` et utilisent `HookDispatcherInterface`.

| Classe | Hook | Effet |
|---|---|---|
| `CleanHead` | `wp_head` (remove) | Supprime `feed_links_extra`, `feed_links`, `rsd_link`, `wlwmanifest_link`, `index_rel_link`, `parent_post_rel_link`, `start_post_rel_link`, `adjacent_posts_rel_link` |
| `RemoveEmojis` | Multiples (remove) | Supprime les scripts/styles emojis sur front, admin et emails |
| `RemoveWordPressVersion` | `wp_head` (remove) + `the_generator` (filter) | Masque `wp_generator` et retourne `false` pour le filtre generator |
| `RemoveSvgFilters` | `wp_body_open` (remove) | Supprime `wp_global_styles_render_svg_filters` et la variante Gutenberg |
| `RemoveNavigationFallback` | `block_core_navigation_render_fallback` (filter) | Retourne `false` pour desactiver le fallback |

### Parametres du conteneur

| Parametre | Description |
|---|---|
| `%themeDirectory%` | Chemin absolu du theme |
| `%themeTextDomain%` | Text domain du theme |

## Explanation

### Pourquoi nettoyer le `<head>` ?

WordPress injecte par defaut de nombreuses balises dans le `<head>` : flux RSS, liens RSD (XML-RPC), Windows Live Writer, emojis, numero de version, etc. La plupart de ces elements sont inutiles pour un site moderne et peuvent :

- **Degrader les performances** (scripts/styles emojis).
- **Exposer des informations de securite** (version WordPress).
- **Polluer le HTML** (liens relationnels, filtres SVG).

Le bundle applique le principe de **securite par defaut** : toutes les actions de nettoyage sont activees automatiquement.

### Architecture

Chaque action de nettoyage est une classe autonome implementant `Hooks`. Elles sont decouvertes par l'autowiring Symfony et executees au boot. Cette granularite permet de desactiver individuellement chaque nettoyage sans impacter les autres.

### Difference avec PluginKernel

`ThemeKernel` et `PluginKernel` heritent tous deux de `AbstractKernel`. La difference reside dans :

- Les noms des parametres DI (`themeDirectory` / `themeTextDomain` vs `pluginDirectory` / `pluginTextDomain`).
- Le hook de chargement des traductions (`after_setup_theme` pour le theme, `init` pour le plugin).
- Le chemin de recherche des fichiers de traduction.
