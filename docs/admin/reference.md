# Admin Bundle — API Reference

*Reference — Information-oriented*

---

## Interfaces

### `AdminPageInterface`

**Namespace:** `BackTo\Framework\Bundle\Admin\Contracts`
**Extends:** `HookInterface`

Defines a top-level admin menu page.

| Method | Return | Description |
|---|---|---|
| `getPageTitle()` | `string` | Page title (`<title>` tag) |
| `getMenuTitle()` | `string` | Text displayed in the menu |
| `getCapability()` | `string` | Required capability (e.g. `manage_options`) |
| `getMenuSlug()` | `string` | Unique menu slug |
| `getIconUrl()` | `string` | URL or dashicon class for the icon |
| `getPosition()` | `?int` | Menu position (`null` = bottom) |
| `render()` | `void` | Page render callback |

---

### `AdminPageRegistrarInterface`

**Namespace:** `BackTo\Framework\Bundle\Admin\Contracts`

Port for registering admin pages with WordPress.

| Method | Return | Description |
|---|---|---|
| `registerMenuPage(array $args)` | `void` | Register a top-level menu page |
| `registerSubmenuPage(string $parentSlug, array $args)` | `void` | Register a submenu page |

---

### `CapabilityManagerInterface`

**Namespace:** `BackTo\Framework\Bundle\Admin\Contracts`

Port for managing roles and capabilities.

| Method | Return | Description |
|---|---|---|
| `addCapToRole(string $role, string $capability)` | `void` | Add a capability to a role |
| `currentUserCan(string $capability)` | `bool` | Check if the current user has the capability |
| `removeSubmenuPage(string $parentSlug, string $menuSlug)` | `void` | Remove a submenu page |

---

## Classes

### `AdminPageRegistry`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Registry collecting `AdminPageInterface` instances. Populated by the compiler pass.

---

### `RegisterAdminPage`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Hooks into `admin_menu`. Iterates over `AdminPageRegistry` and registers each page via `AdminPageRegistrarInterface`.

---

### `AddMenuForEditors`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Grants editors access to the Appearance menu, hides irrelevant submenus (themes, widgets, customizer), and removes the Customizer link from the admin bar.

---

### `AddReusableBlockMenu`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Registers a top-level "Reusable Blocks" menu (`edit.php?post_type=wp_block`) with `dashicons-block-default` at position 30.

---

## Infrastructure

| Class | Description |
|---|---|
| `WordPressAdminPageRegistrar` | Adapter calling `add_menu_page()` / `add_submenu_page()` |
| `WordPressCapabilityManager` | Adapter calling `get_role()`, `current_user_can()`, `remove_submenu_page()` |

**Namespace:** `BackTo\Framework\Bundle\Admin\Infrastructure`

---

## Compiler Pass

### `RegisterAdminPagePass`

**Namespace:** `BackTo\Framework\Bundle\Admin\DependencyInjection\Compiler`

Collects services tagged `wordpress.admin_page` and injects them into `AdminPageRegistry`.

---

## DI Extension

### `AdminExtension`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Registers the `wordpress.admin_page` tag for all classes implementing `AdminPageInterface`.
