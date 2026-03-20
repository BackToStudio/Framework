# Blocks Bundle

Le bundle `BackTo\Framework\Bundle\Blocks` permet d'enregistrer des blocs Gutenberg personnalises et des styles de blocs via le conteneur d'injection de dependances. Il fournit des classes abstraites pour definir des blocs et styles, des registres pour les collecter, et des actions pour les enregistrer aupres de WordPress.

## Tutorial

### Enregistrer un bloc personnalise

1. **Creez une classe etendant `CustomBlock`** :

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;

final class HeroBlock extends CustomBlock
{
    protected string $name = 'mon-theme/hero';
}
```

2. **Pour un bloc dynamique**, implementez egalement `DynamicBlock` :

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;
use BackTo\Framework\Contracts\DynamicBlock;

final class HeroDynamicBlock extends CustomBlock implements DynamicBlock
{
    protected string $name = 'mon-theme/hero-dynamic';

    public function renderBlock(array $attributes, string $content): string
    {
        return '<section class="hero">' . esc_html($attributes['title'] ?? '') . '</section>';
    }
}
```

3. **Enregistrez l'extension** dans votre kernel :

```php
$kernel->addExtension(new BlocksExtension());
```

La classe sera auto-detectee via le tag `wordpress.block` et enregistree au hook `init`.

### Enregistrer un style de bloc

1. **Creez une classe etendant `CustomBlockStyle`** :

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlockStyle;

final class ShadowStyle extends CustomBlockStyle
{
    protected array $blocks = ['core/group', 'core/columns'];
    protected string $styleName = 'shadow';
    protected string $label = 'Ombre portee';
}
```

2. Le style sera automatiquement enregistre pour chaque bloc liste dans `$blocks` au hook `after_setup_theme`.

## How-to

### Remplacer les balises `<img>` SVG par des balises `<svg>` inline

Activez `ReplaceImgBlockBySvgBlock` dans vos services. Cette action se branche sur le filtre `render_block_core/image` et remplace automatiquement les balises `<img>` pointant vers des fichiers SVG par le contenu SVG inline, via `ReplaceImgTagBySvgTag`.

### Appliquer un style a plusieurs blocs simultanement

Listez tous les blocs cibles dans la propriete `$blocks` de votre `CustomBlockStyle` :

```php
protected array $blocks = [
    'core/group',
    'core/cover',
    'core/columns',
    'core/image',
];
```

Le `RegisterBlockStyles` iterera sur chaque bloc et appellera `register_block_style()` pour chacun.

### Eviter les doublons d'enregistrement de blocs

`RegisterBlock` verifie automatiquement via `BlockRegistrarInterface::exists()` si un bloc est deja enregistre dans le `WP_Block_Type_Registry` avant de l'enregistrer. Les doublons sont ignores silencieusement.

## Reference

### Classes abstraites

#### `CustomBlock`

Classe de base pour definir un bloc personnalise. Implemente `BlockInterface`.

| Propriete | Type | Description |
|---|---|---|
| `$name` | `string` | Nom du bloc (ex. `mon-theme/hero`) |

| Methode | Retour | Description |
|---|---|---|
| `getName()` | `string` | Retourne le nom du bloc |

#### `CustomBlockStyle`

Classe de base pour definir un style de bloc. Implemente `BlockStyleInterface`.

| Propriete | Type | Description |
|---|---|---|
| `$blocks` | `string[]` | Noms des blocs auxquels appliquer le style |
| `$styleName` | `string` | Identifiant du style |
| `$label` | `string` | Label affiche dans l'editeur |

| Methode | Retour | Description |
|---|---|---|
| `getStyleName()` | `string` | Retourne l'identifiant du style |
| `getLabel()` | `string` | Retourne le label |
| `getBlocks()` | `string[]` | Retourne la liste des blocs |
| `getProperties()` | `array{name, label}` | Retourne le tableau de proprietes pour `register_block_style()` |

### Interfaces (ports)

| Interface | Description |
|---|---|
| `BlockRegistrarInterface` | `register(string $blockName, array $args)`, `exists(string $blockName)` |
| `BlockStyleRegistrarInterface` | `register(string $blockName, array $styleProperties)` |

### Registres

| Classe | Description |
|---|---|
| `BlockRegistry` | Collecte les instances `BlockInterface` |
| `BlockStyleRegistry` | Collecte les instances `BlockStyleInterface` |

### Actions (hooks)

| Classe | Hook | Description |
|---|---|---|
| `RegisterBlock` | `init` | Enregistre les blocs du registre via `register_block_type()` |
| `RegisterBlockStyles` | `after_setup_theme` | Enregistre les styles via `register_block_style()` |
| `ReplaceImgBlockBySvgBlock` | `render_block_core/image` | Remplace les `<img>` SVG par du SVG inline |

### Adaptateurs (infrastructure)

| Classe | Description |
|---|---|
| `WordPressBlockRegistrar` | Appelle `register_block_type()` et `WP_Block_Type_Registry` |
| `WordPressBlockStyleRegistrar` | Appelle `register_block_style()` |

## Explanation

### Architecture

Le bundle applique le meme pattern ports & adaptateurs que le reste du framework. Les interfaces `BlockRegistrarInterface` et `BlockStyleRegistrarInterface` isolent la logique metier des fonctions WordPress. Les adaptateurs dans `Infrastructure/` effectuent les appels reels.

### Flux d'enregistrement des blocs

1. Les classes `CustomBlock` sont auto-taguees `wordpress.block` par `BlocksExtension`.
2. `RegisterBlockPass` (compiler pass) les collecte dans `BlockRegistry`.
3. Au hook `init`, `RegisterBlock` parcourt le registre, verifie l'existence de chaque bloc, et l'enregistre. Les blocs implementant `DynamicBlock` recoivent un `render_callback`.

### Flux d'enregistrement des styles

1. Les classes `CustomBlockStyle` sont auto-taguees `wordpress.block_style`.
2. `RegisterBlockStylePass` les collecte dans `BlockStyleRegistry`.
3. Au hook `after_setup_theme`, `RegisterBlockStyles` itere sur chaque style et l'applique a chacun de ses blocs cibles.

### Blocs dynamiques vs statiques

Un bloc **statique** est defini entierement cote editeur (JavaScript). Un bloc **dynamique** implemente `DynamicBlock` et fournit un `renderBlock()` PHP qui genere le HTML cote serveur. Le bundle gere les deux cas transparentement.
