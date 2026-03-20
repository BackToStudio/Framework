# Plugin Bundle

Le bundle `BackTo\Framework\Bundle\Plugin` fournit le kernel de demarrage pour les plugins WordPress construits avec le BackTo Framework. Il gere le cycle de vie du plugin (bootstrap, injection de dependances) et le chargement des traductions (text domain) pour les plugins standards et les mu-plugins.

## Tutorial

### Demarrer un plugin avec PluginKernel

1. **Creez votre fichier principal de plugin** :

```php
<?php
/**
 * Plugin Name: Mon Plugin
 * Text Domain: mon-plugin
 */

declare(strict_types=1);

use BackTo\Framework\Bundle\Plugin\PluginKernel;

$kernel = new PluginKernel(
    environment: 'production',
    debug: false,
);

$kernel->setProjectDir(__DIR__);
$kernel->setTextDomain('mon-plugin');
$kernel->boot();
```

2. **Ajoutez vos extensions** (bundles) avant le boot :

```php
$kernel->addExtension(new AdminExtension());
$kernel->addExtension(new BlocksExtension());
$kernel->boot();
```

3. **Creez le dossier `languages/`** a la racine de votre plugin pour les fichiers `.po` / `.mo`.

### Activer les traductions

Les traductions sont chargees automatiquement si le service `LoadPluginTextDomain` est actif. Le kernel injecte les parametres `pluginDirectory` et `pluginTextDomain` dans le conteneur. Le chargement se fait au hook `init`.

## How-to

### Charger les traductions d'un mu-plugin

Utilisez `LoadMuPluginTextDomain` au lieu de `LoadPluginTextDomain`. La difference : il appelle `load_muplugin_textdomain()` qui cherche les traductions dans le dossier `mu-plugins/mon-plugin/languages/`.

Pour l'activer, assurez-vous que le fichier de configuration des services charge le namespace `I18n` :

```php
$services->load('MonPlugin\\I18n\\', 'I18n/*');
```

### Configurer l'environnement

Le `PluginKernel` accepte deux parametres au constructeur :

- `environment` : `'production'`, `'development'`, `'staging'`, etc.
- `debug` : `true` pour activer le mode debug (desactive le cache du conteneur).

```php
$kernel = new PluginKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);
```

### Ajouter des services specifiques au plugin

Creez un fichier `Resources/config/services.php` dans votre plugin :

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->bind('$pluginDirectory', '%pluginDirectory%')
        ->bind('$pluginTextDomain', '%pluginTextDomain%')
        ->autowire()
        ->autoconfigure();

    $services->load('MonPlugin\\', '../src/*');
};
```

## Reference

### Classes

#### `PluginKernel`

Etend `AbstractKernel`. Point d'entree du plugin.

| Methode protegee | Retour | Description |
|---|---|---|
| `getDirectoryParameterName()` | `string` | Retourne `'pluginDirectory'` |
| `getTextDomainParameterName()` | `string` | Retourne `'pluginTextDomain'` |
| `getKernelConfigDir()` | `string` | Chemin vers `Resources/config` |

Methodes heritees de `AbstractKernel` (via traits `TextDomain` et `WordPressContainer`) :

- `setProjectDir(string $dir)` : definit le repertoire du plugin.
- `setTextDomain(string $domain)` : definit le text domain.
- `boot()` : compile le conteneur et demarre les hooks.

#### `LoadPluginTextDomain`

Implemente `Hooks`. Charge les traductions du plugin au hook `init`.

| Parametre injecte | Description |
|---|---|
| `$pluginDirectory` | Repertoire racine du plugin |
| `$pluginTextDomain` | Text domain du plugin |

Appelle `load_plugin_textdomain($domain, false, 'mon-plugin/languages')`.

#### `LoadMuPluginTextDomain`

Identique a `LoadPluginTextDomain` mais appelle `load_muplugin_textdomain()` pour les mu-plugins.

### Interface

#### `TextDomainLoaderInterface`

Port d'abstraction pour le chargement des text domains.

| Methode | Description |
|---|---|
| `loadPluginTextDomain(string $domain, string $pluginRelPath)` | Charge un text domain de plugin |
| `loadMuPluginTextDomain(string $domain, string $muPluginRelPath)` | Charge un text domain de mu-plugin |
| `loadThemeTextDomain(string $domain, string $path)` | Charge un text domain de theme |

### Infrastructure

| Classe | Description |
|---|---|
| `WordPressTextDomainLoader` | Adaptateur appelant les fonctions WordPress natives |

## Explanation

### Cycle de vie du kernel

1. **Construction** : le kernel recoit l'environnement et le flag debug.
2. **Configuration** : `setProjectDir()` et `setTextDomain()` definissent les parametres du conteneur DI.
3. **Boot** : `boot()` cree un `ContainerBuilder`, charge les services (`Resources/config/services.php`), compile le conteneur, puis execute les hooks.

### Parametres du conteneur

Le kernel injecte automatiquement deux parametres dans le conteneur :

- `%pluginDirectory%` : chemin absolu du plugin (utilise pour localiser les ressources).
- `%pluginTextDomain%` : text domain (utilise par les classes I18n).

Ces parametres sont bindes via `$pluginDirectory` et `$pluginTextDomain` dans la configuration des services, ce qui permet l'injection automatique dans les constructeurs.

### Separation plugin / mu-plugin

WordPress distingue les plugins standards (`wp-content/plugins/`) des mu-plugins (`wp-content/mu-plugins/`). Le chargement des traductions differe (`load_plugin_textdomain` vs `load_muplugin_textdomain`). Le bundle fournit deux classes distinctes pour gerer cette difference, tout en partageant la meme interface `TextDomainLoaderInterface`.
