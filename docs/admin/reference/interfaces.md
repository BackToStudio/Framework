# Interfaces

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
