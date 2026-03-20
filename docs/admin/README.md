# Admin Bundle

Le bundle `BackTo\Framework\Bundle\Admin` fournit une couche d'abstraction pour enregistrer des pages d'administration WordPress, gerer les menus et les capabilities des roles utilisateurs. Il s'appuie sur le conteneur d'injection de dependances pour auto-detecter et enregistrer les pages admin via un systeme de tags.

## Tutorial

### Creer votre premiere page d'administration

1. **Creez une classe implementant `AdminPageInterface`** :

```php
<?php

declare(strict_types=1);

namespace App\Admin;

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageInterface;

final class DashboardPage implements AdminPageInterface
{
    public function getPageTitle(): string
    {
        return 'Tableau de bord';
    }

    public function getMenuTitle(): string
    {
        return 'Mon Dashboard';
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'mon-dashboard';
    }

    public function getIconUrl(): string
    {
        return 'dashicons-dashboard';
    }

    public function getPosition(): ?int
    {
        return 2;
    }

    public function render(): void
    {
        echo '<div class="wrap"><h1>Mon Dashboard</h1></div>';
    }

    public function hooks(): void
    {
        // Aucun hook supplementaire necessaire.
    }
}
```

2. **Enregistrez l'extension** dans votre kernel :

```php
$kernel->addExtension(new AdminExtension());
```

3. Votre classe sera auto-detectee grace au tag `wordpress.admin_page` et enregistree automatiquement via `RegisterAdminPagePass`.

## How-to

### Ajouter un menu "Blocs reutilisables" dans l'admin

Activez la classe `AddReusableBlockMenu` dans votre configuration de services. Elle enregistre automatiquement un menu de premier niveau pointant vers `edit.php?post_type=wp_block` avec l'icone `dashicons-block-default` a la position 30.

### Donner acces au menu Apparence aux editeurs

Activez `AddMenuForEditors`. Cette action :

- Ajoute la capability `edit_theme_options` au role `editor`.
- Masque les sous-menus inutiles (themes, widgets, customizer) pour les editeurs.
- Supprime le lien Customizer de la barre d'admin.

### Enregistrer un sous-menu

Utilisez `AdminPageRegistrarInterface::registerSubmenuPage()` :

```php
$this->registrar->registerSubmenuPage('mon-parent-slug', [
    'page_title'  => 'Sous-page',
    'menu_title'  => 'Sous-page',
    'capability'  => 'manage_options',
    'menu_slug'   => 'ma-sous-page',
    'callback'    => [$this, 'render'],
]);
```

## Reference

### Interfaces

#### `AdminPageInterface`

Etend `HookInterface`. Definit une page de menu admin.

| Methode | Retour | Description |
|---|---|---|
| `getPageTitle()` | `string` | Titre de la page (balise `<title>`) |
| `getMenuTitle()` | `string` | Texte affiche dans le menu |
| `getCapability()` | `string` | Capability requise (ex. `manage_options`) |
| `getMenuSlug()` | `string` | Slug unique du menu |
| `getIconUrl()` | `string` | URL ou classe dashicon de l'icone |
| `getPosition()` | `?int` | Position dans le menu (`null` = fin) |
| `render()` | `void` | Callback de rendu de la page |

#### `AdminPageRegistrarInterface`

Port d'abstraction pour l'enregistrement de pages admin.

| Methode | Description |
|---|---|
| `registerMenuPage(array $args)` | Enregistre un menu de premier niveau |
| `registerSubmenuPage(string $parentSlug, array $args)` | Enregistre un sous-menu |

#### `CapabilityManagerInterface`

Port pour la gestion des roles et capabilities.

| Methode | Description |
|---|---|
| `addCapToRole(string $role, string $capability)` | Ajoute une capability a un role |
| `currentUserCan(string $capability)` | Verifie si l'utilisateur courant a la capability |
| `removeSubmenuPage(string $parentSlug, string $menuSlug)` | Supprime un sous-menu |

### Classes principales

| Classe | Role |
|---|---|
| `AdminPageRegistry` | Registre collectant les instances `AdminPageInterface` |
| `RegisterAdminPage` | Hook `admin_menu` : parcourt le registre et enregistre chaque page |
| `AddMenuForEditors` | Accorde l'acces Apparence aux editeurs, masque le customizer |
| `AddReusableBlockMenu` | Ajoute un menu "Blocs reutilisables" |
| `WordPressAdminPageRegistrar` | Adaptateur appelant `add_menu_page()` / `add_submenu_page()` |
| `WordPressCapabilityManager` | Adaptateur appelant `get_role()`, `current_user_can()`, `remove_submenu_page()` |
| `RegisterAdminPagePass` | Compiler pass : collecte les services tagges `wordpress.admin_page` |

## Explanation

### Architecture hexagonale

Le bundle suit une architecture ports & adaptateurs. Les interfaces `AdminPageRegistrarInterface` et `CapabilityManagerInterface` constituent les **ports** (contrats du domaine). Les classes dans `Infrastructure/` (`WordPressAdminPageRegistrar`, `WordPressCapabilityManager`) sont les **adaptateurs** qui appellent directement les fonctions WordPress. Cette separation permet de tester la logique metier sans charger WordPress.

### Auto-configuration

`AdminExtension` enregistre un tag `wordpress.admin_page` pour toute classe implementant `AdminPageInterface`. Le `RegisterAdminPagePass` (compiler pass Symfony) collecte ces services et les injecte dans `AdminPageRegistry`. Au runtime, `RegisterAdminPage` se branche sur le hook `admin_menu` et itere sur le registre pour creer les pages.

### Separation des responsabilites

- `AdminPageRegistry` : collecte (pattern Registry).
- `RegisterAdminPage` : orchestration (pattern Action / Hook).
- `AdminPageInterface` : definition (contrat).

Chaque page admin est une classe autonome, testable unitairement, sans couplage direct aux fonctions WordPress.
