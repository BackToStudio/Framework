# Admin

WordPress admin page management.

## Classes

| Class | Role |
|-------|------|
| `AdminPageRegistry` | Collects registered admin pages |
| `RegisterAdminPage` | Application orchestrator (hooks into `admin_menu`) |
| `Contracts\AdminPageInterface` | Interface for admin page definitions |
| `Contracts\AdminPageRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressAdminPageRegistrar` | WP adapter (`add_menu_page`/`add_submenu_page`) |
| `AddReusableBlockMenu` | Adds reusable blocks menu page |
| `AddMenuForEditors` | Grants editor role theme options access |

## Contracts

### `AdminPageInterface` extends `HookInterface`

Represents an admin menu page to be registered.

```php
interface AdminPageInterface extends HookInterface
{
    public function getPageTitle(): string;
    public function getMenuTitle(): string;
    public function getCapability(): string;
    public function getMenuSlug(): string;
    public function getIconUrl(): string;
    public function getPosition(): ?int;
    public function render(): void;
}
```
