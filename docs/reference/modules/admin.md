# Admin

WordPress admin page management.

## Contracts

### `AdminPageInterface` extends `HookInterface`

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
